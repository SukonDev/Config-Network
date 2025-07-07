<?php
// File: ip_config.php
// Description: ระบบกำหนดค่า IP ขั้นพื้นฐานสำหรับการจัดการเครือข่ายขนาดใหญ่

class IPConfig {
    private $db;

    // Constructor: เชื่อมต่อฐานข้อมูล
    public function __construct() {
        try {
            $this->db = new PDO(
                'mysql:host=localhost;dbname=network_config',
                'username',
                'password',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    // ตรวจสอบ IP Address (IPv4)
    public function validateIP($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    // ตรวจสอบ Subnet Mask
    public function validateSubnetMask($subnet) {
        $validMasks = [
            '255.255.255.255', '255.255.255.254', '255.255.255.252',
            '255.255.255.248', '255.255.255.240', '255.255.255.224',
            '255.255.255.192', '255.255.255.128', '255.255.255.0',
            '255.255.254.0', '255.255.252.0', '255.255.248.0',
            '255.255.240.0', '255.255.224.0', '255.255.192.0',
            '255.255.128.0', '255.255.0.0', '255.254.0.0', '255.252.0.0',
            '255.248.0.0', '255.240.0.0', '255.224.0.0', '255.192.0.0',
            '255.128.0.0', '255.0.0.0', '254.0.0.0', '252.0.0.0',
            '248.0.0.0', '240.0.0.0', '224.0.0.0', '192.0.0.0',
            '128.0.0.0', '0.0.0.0'
        ];
        return in_array($subnet, $validMasks);
    }

    // Calculate Network Address
    public function calculateNetworkAddress($ip, $subnet) {
        if (!$this->validateIP($ip) || !$this->validateSubnetMask($subnet)) {
            throw new Exception("Invalid IP or Subnet Mask");
        }
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        return long2ip($ipLong & $subnetLong);
    }

    // บันทึก การกำหนดค่า IP ให้กับฐานข้อมูล
    public function saveIPConfig($device_id, $ip, $subnet, $gateway) {
        try {
            if (!$this->validateIP($ip) || !$this->validateSubnetMask($subnet) || !$this->validateIP($gateway)) {
                throw new Exception("Invalid IP, Subnet Mask, or Gateway");
            }

            $network = $this->calculateNetworkAddress($ip, $subnet);
            $sql = "INSERT INTO ip_configurations (device_id, ip_address, subnet_mask, gateway, network_address)
                    VALUES (:device_id, :ip, :subnet, :gateway, :network)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':device_id' => $device_id,
                ':ip' => $ip,
                ':subnet' => $subnet,
                ':gateway' => $gateway,
                ':network' => $network
            ]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to save IP configuration: " . $e->getMessage());
        }
    }

    // ดึงข้อมูลการกำหนดค่า IP ตาม ID อุปกรณ์
    public function getIPConfig($device_id) {
        try {
            $sql = "SELECT * FROM ip_configurations WHERE device_id = :device_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':device_id' => $device_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve IP configuration: " . $e->getMessage());
        }
    }
}

// ตัวอย่างการใช้งาน
try {
    $ipConfig = new IPConfig();
    
    // ข้อมูลตัวอย่าง
    $device_id = 1;
    $ip = "192.168.1.100";
    $subnet = "255.255.255.0";
    $gateway = "192.168.1.1";

    // บันทึกการกำหนดค่า
    $ipConfig->saveIPConfig($device_id, $ip, $subnet, $gateway);
    echo "IP Configuration saved successfully!\n";

    // ดึงข้อมูลการกำหนดค่า
    $config = $ipConfig->getIPConfig($device_id);
    print_r($config);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
