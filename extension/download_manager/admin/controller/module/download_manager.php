<?php
/*
 *  location: admin/controller
 */

namespace Opencart\Admin\Controller\Extension\DownloadManager\Module;

class DownloadManager extends \Opencart\System\Engine\Controller
{
    private $codename = 'download_manager';
    private $route = 'extension/download_manager/module/download_manager';
    private $config_file = 'download_manager';
    private $sub_versions = array();
    private $error = array();
    public $store_id = 0;

    public function __construct($registry)
    {
        parent::__construct($registry);
    }

    public function index()
    {
        // if (!class_exists('d_simple_html_dom')) {        
        //     include_once DIR_OPENCART . 'extension/download_manager/system/library/d_simple_html_dom.php';
        // }
        // Loading Library Opencart\System\Library\d_simple_html_dom
        // Getting error Exception: Error: Could not load library d_simple_html_dom! in /Applications/MAMP/htdocs/opencart.loc/system/engine/loader.php on line 192
        // This works fine if I include file in code, but if it possible to load library without including it?
        $html_dom = $this->load->library('d_simple_html_dom');
        
        $html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
        

        $output = (string)$html_dom;
    }
}
