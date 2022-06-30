<?php

/*
 *  location: admin/model
 */
namespace Opencart\Admin\Model\Extension\DownloadManager\Module;
class DownloadManager extends  \Opencart\System\Engine\Model
{

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('tool/image');
    }

    public function getOrderDetails()
    {
        $query = $this->db->query("SELECT DISTINCT o.order_id, o.order_status_id, o.date_modified
                                   FROM " . DB_PREFIX . "order o, " . DB_PREFIX . "order_product op, " . DB_PREFIX . "product_to_download ptd
                                   WHERE o.order_id = op.order_id AND op.product_id = ptd.product_id");
        return $query->rows;
    }

    public function getDownloadDetails()
    {
        $query = $this->db->query("SELECT d.download_id
                                   FROM " . DB_PREFIX . "download d");
        return $query->rows;
    }

    public function changeStatusForDownload($statuses)
    {
        foreach ($statuses as $download_id => $status)
            $this->db->query("UPDATE " . DB_PREFIX . "ddm_download
                              SET status = " . $status . "
                              WHERE download_id = " . $download_id . "");
    }

    public function getDownloadPeriodForDownload($download_id)
    {
        $query = $this->db->query("SELECT download_period FROM " . DB_PREFIX . "ddm_download WHERE download_id = " . $download_id);
        return $query->row;
    }

    public function getDownloadRemote($download_id)
    {
        $query = $this->db->query("SELECT link, type FROM " . DB_PREFIX . "ddm_download WHERE download_id = " . $download_id);
        return $query->row;
    }

    public function changeStatus($statuses)
    {
        foreach ($statuses as $order_id => $status)
            $this->db->query("UPDATE " . DB_PREFIX . "ddm_order
                              SET status = " . $status . "
                              WHERE order_id = " . $order_id . "");
    }

    public function getDownloadsInOrder($order_id)
    {
        $query = $this->db->query("SELECT COUNT(order_id) as ids FROM " . DB_PREFIX . "ddm_download_to_order WHERE order_id = " . $order_id);
        return $query->row['ids'];
    }

    public function isInstalled($code, $type = false) {
        $sql = "SELECT * FROM " . DB_PREFIX . "extension WHERE ";
        if($type){
            $sql .= "`type` = '" . $this->db->escape($type) . "' AND ";
        }
        $sql .= "`code` = '" . $this->db->escape($code) . "'";    
  
        $query = $this->db->query($sql);
        if(!empty($query->row)){
            return true;
        }
        return false;
    }

    public function getDetailsOrder($data = array())
    {
        $sql = "SELECT DISTINCT           dm.order_id,
                                          dm.status,
                                          o.customer_id,
                                          cust.firstname, cust.lastname,
                                          os.name as order_status,
                                          o.date_added,
                                          download_period
                                   FROM
                                          " . DB_PREFIX . "order_product op,
                                          " . DB_PREFIX . "product_to_download ptd,
                                          " . DB_PREFIX . "ddm_order dm,
                                          " . DB_PREFIX . "order o,
                                          " . DB_PREFIX . "customer cust,
                                          " . DB_PREFIX . "order_status os
                                   WHERE os.order_status_id = o.order_status_id
                                         AND  o.order_id = dm.order_id
                                         AND  o.order_id = op.order_id
                                         AND  op.product_id = ptd.product_id
                                         AND  o.customer_id = cust.customer_id
                                         ORDER BY dm.order_id DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = 10;
            }
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        
        return $query->rows;
    }

    public function linkNewProduct($download_id, $product_id)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "ddm_product_to_download
                              (product_id, download_id) VALUES (" . $product_id . ", " . $download_id . ")");
    }

    public function getOrdersWithDownload($download_id)
    {
        $query = $this->db->query("SELECT COUNT(order_id) as orders FROM " . DB_PREFIX . "ddm_download_to_order WHERE download_id = " . $download_id);
        return $query->row['orders'];
    }

    public function addSerialKey($data)
    {
        $this->db->query("INSERT INTO  " . DB_PREFIX . "ddm_download_to_serial (download_id, serial_key, sold) VALUES (" . $data['download_id'] . ", '" . $data['serial_key'] . "', 0)");
        $query = $this->db->query("SELECT download_serial FROM " . DB_PREFIX . "ddm_download_to_serial WHERE download_id = " . $data['download_id'] . " AND serial_key = '" . $data['serial_key'] . "'");
        return $query->row['download_serial'];
    }

    public function editSerialKey($data)
    {
        $this->db->query("UPDATE  " . DB_PREFIX . "ddm_download_to_serial SET serial_key = " . $data['serial_key'] . " WHERE download_serial = " . $data['download_serial']);
    }

    public function getDownloadAvailableSerialKeys($download_id)
    {
        $query = $this->db->query("SELECT serial_key, download_serial FROM " . DB_PREFIX . "ddm_download_to_serial WHERE download_id = " . $download_id . " AND sold = 0");
        return $query->rows;
    }

    public function getDownloadSoldSerialKeys($download_id)
    {
        $query = $this->db->query("SELECT serial_key, download_serial FROM " . DB_PREFIX . "ddm_download_to_serial WHERE download_id = " . $download_id . " AND sold = 1");
        return $query->rows;
    }

    public function checkDownloadSerialKey($download_id, $serial_key)
    {
        $query = $this->db->query("SELECT serial_key FROM " . DB_PREFIX . "ddm_download_to_serial WHERE download_id = " . $download_id . " AND serial_key = '" . $serial_key . "'");
        return $query->row;
    }

    public function updateDownloadSerialKeys($download_serial, $serial_key)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "ddm_download_to_serial SET serial_key = '" . $serial_key . "' WHERE download_serial = " . $download_serial);
    }

    public function removeSerialKey($download_serial)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "ddm_download_to_serial WHERE download_serial = " . $download_serial);
    }

    public function getDownloads($data = array())
    {
        $sql = "SELECT DISTINCT           d.download_id,
                                          dd.name,
                                          dm.type,
                                          dm.key_type,
                                          dm.public_type,
                                          dm.link,
                                          dm.download_period,
                                          d.date_added,
                                          dm.download_period,
                                          dm.status
                                   FROM " . DB_PREFIX . "download d,
                                        " . DB_PREFIX . "download_description dd,
                                        " . DB_PREFIX . "ddm_download dm
                                   WHERE  d.download_id = dm.download_id AND dd.download_id = dm.download_id AND dd.language_id = " . $this->config->get('config_language_id') . "
                                   ORDER BY d.download_id DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = 10;
            }
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getDetailsProduct($data = array())
    {
        $sql = "SELECT DISTINCT           p.product_id,
                                          pd.name as product_name,
                                          p.model as product_model,
                                          p.price as product_price,
                                          p.status as product_status,
                                          p.image as product_image
                                    FROM
                                          " . DB_PREFIX . "product_description pd,
                                          " . DB_PREFIX . "product p,
                                          " . DB_PREFIX . "product_to_download ptd
                                    WHERE p.product_id = pd.product_id AND p.product_id = ptd.product_id AND pd.language_id = " . $this->config->get('config_language_id') . "
                                    ORDER BY p.product_id DESC";
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = 10;
            }
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProductDownloads($product_id)
    {
        $query = $this->db->query("SELECT dd.name, dd.download_id
                                   FROM " . DB_PREFIX . "product_to_download ptd, " . DB_PREFIX . "download_description dd
                                   WHERE ptd.product_id = " . $product_id . " AND dd.download_id = ptd.download_id AND dd.language_id = " . $this->config->get('config_language_id'));
        return $query->rows;
    }

    public function getDownloadsInProduct($download_id)
    {
        $query = $this->db->query("SELECT DISTINCT pd.name, p.model, p.price, p.status, pd.product_id, p.image as product_image, dm.download_period
                                   FROM " . DB_PREFIX . "product_to_download ptd, " . DB_PREFIX . "product_description pd, " . DB_PREFIX . "product p,  " . DB_PREFIX . "ddm_product_to_download dm
                                   WHERE ptd.product_id = pd.product_id AND ptd.download_id = " . $download_id . " AND p.product_id = ptd.product_id AND dm.product_id = pd.product_id AND dm.download_id = " . $download_id . " AND p.product_id = dm.product_id
                                   AND pd.language_id = " . $this->config->get('config_language_id'));
        return $query->rows;
    }

    public function removeDownload($download_id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "download WHERE download_id = '" . (int)$download_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "download_description WHERE download_id = '" . (int)$download_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "ddm_download WHERE download_id = '" . (int)$download_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE download_id = '" . (int)$download_id . "'");

    }

    public function getDownloadSerialType($download_id)
    {
        $query = $this->db->query("SELECT key_type FROM " . DB_PREFIX . "ddm_download WHERE download_id = " . $download_id);
        return $query->row['key_type'];
    }

    public function setDependency($id, $days, $flag, $link, $type, $key_type, $public_type)
    {
        if ($flag == 1) {
            $sql = "INSERT INTO " . DB_PREFIX . "ddm_download (status, download_id, download_period, link, type, key_type, public_type) VALUES (1, " . (int)$id . ", ";
   
            if ($days != NULL) {
                $sql .= (int)$days . ", ";
            } else {
                $sql .= " NULL, ";
            }
            if ($link != NULL) {
                $sql .= "'" . $link . "', ";
            } else {
                $sql .= " NULL, ";
            }
            $sql .= $type . ", ";
            $sql .= $key_type . ", ";
            $sql .= $public_type . ")";
            $this->db->query($sql);

        } elseif ($flag == 0) {
            $sql = "UPDATE " . DB_PREFIX . "ddm_download SET download_period = ";
            if ($days != NULL) {
                $sql .= (int)$days . ", ";
            } else {
                $sql .= "NULL, ";
            }
            $sql .= "link = ";
            if ($link != NULL) {
                $sql .= "'" . $link . "', ";
            } else {
                $sql .= "NULL, ";
            }
            $sql .= "type = " . $type . ", ";
            $sql .= "key_type = " . $key_type . ", ";
            $sql .= "public_type = " . $public_type . " ";
            $sql .= " WHERE download_id = " . $id;
            
            $this->db->query($sql);
        }
    }

    public function unlinkProduct($data)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = " . $data['product_id'] . "
                          AND download_id = " . $data['download_id']);
        $this->db->query("DELETE FROM " . DB_PREFIX . "ddm_product_to_download WHERE product_id = " . $data['product_id'] . "
                          AND download_id = " . $data['download_id']);
    }

    public function unlinkAfterEditProduct($product_id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = " . $product_id);
        $this->db->query("DELETE FROM " . DB_PREFIX . "ddm_product_to_download WHERE product_id = " . $product_id);
    }

    public function setDateForDownloadInOrder($data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "ddm_download_to_order
                          SET date_expired = '" . $data['downloadDate'] . "'
                          WHERE download_id = " . $data['download_id'] . " AND order_id = " . $data['order_id']);
    }

    public function resetDateForDownloadInOrder($data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "ddm_download_to_order
                          SET date_expired = NULL
                          WHERE download_id = " . $data['download_id'] . " AND order_id = " . $data['order_id']);
    }

    public function setDateForOrder($data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "ddm_order
                          SET download_period = " . $data['orderDays'] . "
                          WHERE order_id = " . $data['order_id']);
    }

    public function getDownloadDateExpire($download_id, $order_id)
    {
        $query = $this->db->query("SELECT dm.date_expired FROM " . DB_PREFIX . "ddm_download_to_order dm WHERE download_id = " . $download_id . " AND order_id = " . $order_id);

        return $query->row;
    }

    public function resetDateForOrder($data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "ddm_order
                          SET download_period = NULL
                          WHERE order_id = " . $data['order_id']);
    }

    public function setDateForDownload($data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "ddm_download
                          SET download_period = " . $data['downloadDays'] . "
                          WHERE download_id = " . $data['download_id']);
    }

    public function resetDateForDownload($data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "ddm_download
                          SET download_period = NULL
                          WHERE download_id = " . $data['download_id']);
    }

    public function setDateForProduct($data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "ddm_product_to_download
                          SET download_period = " . $data['productDays'] . "
                          WHERE product_id = " . $data['product_id'] . " AND download_id = " . $data['download_id'] . "");
    }

    public function resetDateForProduct($data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "ddm_product_to_download
                          SET download_period = NULL
                          WHERE download_id = " . $data['download_id'] . " AND product_id = " . $data['product_id']);
    }

    public function removeDependency($data)
    {
        foreach ($data as $key => $download_id) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "ddm_download WHERE download_id = " . $download_id);
        }
    }

    public function setDownloadDescription($download_id, $description, $language_id)
    {
        $this->db->query("INSERT INTO  " . DB_PREFIX . "ddm_download_description (download_id, description, language_id) VALUES (" . $download_id . ", '" . $description . "', " . $language_id . ")");
    }

    public function getDownloadPublicType($download_id)
    {
        $query = $this->db->query("SELECT public_type FROM " . DB_PREFIX . "ddm_download WHERE download_id = " . $download_id);
        return $query->row['public_type'];
    }

    public function getDownloadDescription($download_id, $language_id)
    {
        $query = $this->db->query("SELECT description FROM " . DB_PREFIX . "ddm_download_description WHERE download_id = " . $download_id . " AND language_id = " . $language_id);

        if(!empty($query->row['description'])){
            return $query->row['description'];
        } else {
            return '';
        }
    }

    public function updateDownloadDescription($download_id, $description, $language_id)
    {
        $suery = $this->db->query("SELECT description FROM " . DB_PREFIX . "ddm_download_description WHERE download_id = " . $download_id . " AND language_id = " . $language_id);
        if(empty($suery->row['description'])){
            $this->db->query("INSERT INTO  " . DB_PREFIX . "ddm_download_description (download_id, description, language_id) VALUES (" . $download_id . ", '" . $description . "', " . $language_id . ")");
        } else {
            $this->db->query("UPDATE " . DB_PREFIX . "ddm_download_description SET description = '" . $description . "' WHERE download_id = " . $download_id . " AND language_id = " . $language_id);
        }
    }

    public function getCustomerDownloads($customer_id)
    {
        $query = $this->db->query("SELECT DISTINCT d.download_id, dd.name, d.mask
                                   FROM " . DB_PREFIX . "download d, " . DB_PREFIX . "download_description dd, " . DB_PREFIX . "customer c, " . DB_PREFIX . "order o, " . DB_PREFIX . "order_product op, " . DB_PREFIX . "product_to_download ptd
                                   WHERE " . $customer_id . " = o.customer_id
                                   AND o.order_id = op.order_id
                                   AND ptd.download_id = d.download_id
                                   AND dd.download_id = d.download_id");
        return $query->rows;
    }

    public function createTables($settings)
    { 
        $checkColumns = array();
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ddm_order` (
            `status` INT,
            `order_id` INT NOT NULL,
            `download_period` INT)");
        $order_details = $this->getOrderDetails();
        if (empty($settings)) {
            foreach ($order_details as $key => $order) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "ddm_order (status, order_id)
                              VALUES (1, " . $order['order_id'] . ")");
            }
        }
        
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ddm_download` (
            `download_id` INT NOT NULL,
            `download_period` INT,
            `download_times` INT DEFAULT 0,
            `comment` VARCHAR(100),
            `link` VARCHAR(150),
            `type` INT,
            `status` INT)");
        $query = $this->db->query("DESCRIBE `" . DB_PREFIX . "ddm_download`");
        foreach ($query->rows as $row){
            $checkColumns['columns'][] = $row['Field'];
        }

        if (!in_array("key_type", $checkColumns)) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "ddm_download` ADD key_type INT NOT NULL DEFAULT 0");
        }

        if (!in_array("public_type", $checkColumns)) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "ddm_download` ADD public_type INT NOT NULL DEFAULT 0");
        }

        $download_details = $this->getDownloadDetails();
        if (empty($settings)) {
            foreach ($download_details as $key => $order) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "ddm_download (status, download_id, type)
                              VALUES (1, " . $order['download_id'] . ", 0)");
            }
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ddm_download_to_order` (
            `order_id` INT NOT NULL,
            `download_id` INT NOT NULL,
            `date_expired` DATE)");
        $download_order = $this->db->query("SELECT op.order_id, ptd.download_id FROM " . DB_PREFIX . "order_product op, " . DB_PREFIX . "product_to_download ptd WHERE op.product_id = ptd.product_id ");
        if (empty($settings)) {
            foreach ($download_order->rows as $dependency) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "ddm_download_to_order (download_id, order_id)
                              VALUES (" . $dependency['download_id'] . ", " . $dependency['order_id'] . ")");
            }
        }


        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ddm_product_to_download` (
            `product_id` INT NOT NULL,
            `download_id` INT NOT NULL,
            `download_period` INT)");
        $data = $this->db->query("SELECT product_id, download_id FROM " . DB_PREFIX . "product_to_download");
        if(empty($settings)){
            foreach ($data->rows as $dependency) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "ddm_product_to_download (product_id, download_id)
                              VALUES (" . $dependency['product_id'] . ", " . $dependency['download_id'] . ")");
            }
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ddm_download_to_serial` (
            `download_id` INT NOT NULL,
            `download_serial` INT NOT NULL AUTO_INCREMENT,
            `serial_key` VARCHAR(100),
            `sold` INT NOT NULL,
            PRIMARY KEY (download_serial))");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ddm_order_download_serial` (
            `order_id` INT NOT NULL,
            `download_id` INT NOT NULL,
            `serial_key` VARCHAR(100))");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ddm_download_description` (
            `download_id` INT NOT NULL,
            `description` VARCHAR(300),
            `language_id` INT NOT NULL)");
    }

    public function getAnalyticsData()
    {
        $query = $this->db->query("SELECT ddm.download_id, dd.name, d.mask, ddm.download_times, ddm.comment
                          FROM " . DB_PREFIX . "ddm_download ddm, " . DB_PREFIX . "download_description dd, " . DB_PREFIX . "download d
                          WHERE ddm.download_id = d.download_id
                          AND dd.download_id = ddm.download_id
                          AND dd.download_id = d.download_id
                          AND ddm.download_times != 0
                          AND dd.language_id = " . $this->config->get('config_language_id') . "
                          ORDER BY ddm.download_times DESC
                          LIMIT 10");
        return $query->rows;
    }

    public function setDownloadSerialKeys($download_id, $serial_key)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "ddm_download_to_serial (download_id, serial_key) VALUES (" . $download_id . ", '" . $serial_key . "')");
    }

    public function clearAnalyticHistory($download_id)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "ddm_download SET download_times = 0 WHERE download_id = " . $download_id);
    }

    public function saveAnalyticComment($download_id, $comment)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "ddm_download SET comment = '" . $comment . "' WHERE download_id = " . $download_id);
    }

    public function dropTables()
    {
        $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "ddm_order");
        $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "ddm_download");
        $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "ddm_download_to_order");
        $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "ddm_product_to_download");
        $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "ddm_download_to_serial");
        $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "ddm_order_download_serial");
        $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "ddm_download_description");
    }

    public function getConfigFile($id, $sub_versions = array())
    {
        if (isset($this->request->post['config'])) {
            return $this->request->post['config'];
        }

        $setting = $this->config->get($id . '_setting');

        if (isset($setting['config'])) {
            return $setting['config'];
        }

        $full = DIR_SYSTEM . 'config/' . $id . '.php';
        if (file_exists($full)) {
            return $id;
        }

        foreach ($sub_versions as $lite) {
            if (file_exists(DIR_SYSTEM . 'config/' . $id . '_' . $lite . '.php')) {
                return $id . '_' . $lite;
            }
        }

        return false;
    }
}
