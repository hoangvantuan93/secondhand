<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Product Controller
 */

class Cproduct extends CI_Controller
{
    private $callFunction;
    public function index() {
        $callFunction = new IndexMng($this);
        $callFunction->excute();
    }

    public function edit() {
        $callFunction = new EditMng($this);
        $callFunction->excute();
    }

    public function delete() {
        $callFunction = new DeleteMng($this);
        $callFunction->excute();
    }

    public function details() {
        $callFunction = new DetailsMng($this);
        $callFunction->excute();
    }
    public function insert() {
        $callFunction = new InsertMng($this);
        $callFunction->excute();
    }
}

/**
 *  Interface
 */
interface CallFunction {
    public function excute();
}

/**
 *  Index Manager
 */
class IndexMng implements CallFunction
{
    private $that;
    function IndexMng($that) {
        $this->that = $that;
    }
    public function excute() {

        // Check is user ?
        if (!$this->that->session->userdata('user')) redirect(base_url());

        // Get data of current user
        $username = $this->that->session->userdata('user');
        $user_id = $this->that->muser->findId($username);
        $data['product'] = $this->that->mproduct->findAllById($user_id);
        foreach ($data as $key) {
            foreach ($key as $value) {
                $category = $this->that->mcategory->find($value->category_id);
                $value->category = $category;
            }
        }

        // Load view
        $this->that->load->view('product/list', $data);
    }
    public function getThat() {
        return $this->that;
    }
}

/**
 *  Edit Image
 */
class EditMng implements CallFunction
{
    private $that;
    function EditMng($that) {
        $this->that = $that;
    }
    public function excute() {

        //Getdata
        $data['product'] = $this->that->mproduct->find($this->that->input->get('id'));
        $data['category'] = $this->that->mcategory->findAll();

        // Config path image
        $old_path_name = APPPATH . '../assets/uploads/';

        // Submit was click
        if ($this->that->input->post('name')) {

            // Init product to update
            $product = array('name' => $this->that->input->post('name'), 'price' => $this->that->input->post('price'), 'category_id' => $this->that->input->post('category'), 'description' => $this->that->input->post('description'));

            // Instant process image
            $imageProcess = new ImageProcess($this->that);

            // Instant config image
            $myConfig = new MyConfig;

            // Upload
            $imageProcess->setConfig($myConfig->getConfigUpload());

            // If upload error
            if (!$imageProcess->getUpload()->upload(new uploadUserLib())) {
                $error = $imageProcess->getUpload()->getError();
            }
            else {

                // Get image that uploaded
                $image_data = $imageProcess->getUpload()->getImage();

                // Resize image
                $imageProcess->setConfig($myConfig->getConfigResize());
                $imageProcess->getResize()->resize(new resizeUserLib());

                // Rename image
                $rename = new Rename($image_data, $old_path_name, new getNameByTime());
                $rename->excute();

                // Add link image to upload
                $product['image'] = 'assets/uploads/' . $rename->getNewName()->getName();
            }

            // Upload
            $this->that->mproduct->update($this->that->input->get('id'), $product);
            redirect('cproduct');
        }
        else $this->that->load->view('product/edit', $data, FALSE);
    }
    public function getThat() {
        return $this->that;
    }
}

/**
 * Insert Manager
 */
class InsertMng implements CallFunction
{
    private $that;
    function __construct($that) {
        $this->that = $that;
    }

    public function excute() {

        // Init Data
        $data['category'] = $this->that->mcategory->findAll();
        $data['error'] = '';

        // Config path image
        $old_path_name = APPPATH . '../assets/uploads/';

        // Submit was click
        if ($this->that->input->post('name')) {

            // Init image process
            $imageProcess = new ImageProcess($this->that);

            // Init image Config
            $myConfig = new MyConfig();

            // Get data product
            $data['product'] = $this->getDataProduct();

            $imageProcess->setConfig($myConfig->getConfigUpload());
            if (!$imageProcess->getUpload()->upload(new uploadUserLib())) {
                $data['error'] = $imageProcess->getUpload()->getError();
            }
            else {

                // Get image that uploaded
                $image_data = $imageProcess->getUpload()->getImage();

                // Resize image
                $imageProcess->setConfig($myConfig->getConfigResize());
                $imageProcess->getResize()->resize(new resizeUserLib());

                // Rename image
                $rename = new Rename($image_data, $old_path_name, new getNameByTime());
                $rename->excute();

                // Add link image to upload
                $data['product']['image'] = 'assets/uploads/' . $rename->getNewName()->getName();
                echo $data['product']['image'];
            }

            $this->that->mproduct->insert($data['product']);
            redirect('cproduct');
        }

        // Submit wasn't click
        else {
            $this->that->load->view('product/insert', $data);
        }
    }

    // Get data Product was post
    public function getDataProduct() {

        $user_id = $this->that->muser->findId($this->that->session->userdata('user'));
        $product = array('user_id' => $user_id, 'status' => 'Ready', 'name' => $this->that->input->post('name'), 'price' => $this->that->input->post('price'), 'category_id' => $this->that->input->post('category'), 'description' => $this->that->input->post('description'), 'image' => 'assets/image/common/imgnotfound.jpg');
        return $product;
    }
    public function getThat() {
        return $this->that;
    }
}

/**
 *  Details product
 */
class DetailsMng
{
    private $that;
    function __construct($that) {

        // code...
        $this->that = $that;
    }

    public function excute() {

        $data = $this->getDataProduct();
        $this->that->load->view('product/details', $data);
    }

    public function getDataProduct() {
        $data['product'] = $this->that->mproduct->find($this->that->input->get('id'));
        if ($data['product'] == null) redirect(base_url());
        $data['user'] = $this->that->muser->find($data['product']->user_id);
        $category = $this->that->mcategory->find($data['product']->category_id);
        $data['product']->category_name = $category->name;
        $data['category_name'] = $category->name;

        $currentUser = $this->that->session->userdata('user');
        $currentUserId = $this->that->session->userdata('id');
        $data['isMe'] = $data['user']->username == $currentUser ? true : false;

        $data['all'] = null;
        $data['suggess'] = null;

        /*
         *Neu session hien tai la minh
         *Lay du lieu san pham tren toan sever va san pham goi y
         *
         *Neu session khong phai la minh
         *Lay tat ca du lieu cua minh vaf san pham goi y
        */
        if ($data['isMe']) {

            // find all product in database
            $data['all'] = $this->that->mproduct->findAllNotMe($currentUserId);
        }
        else {

            //Find product
            $data['all'] = $this->that->mproduct->findAllById($currentUserId);
        }
        foreach ($data['all'] as $product) {
            $delta = $data['product']->price - $product->price;
            if ($delta >= - 200000 && $delta <= 200000) {
                $data['suggess'][] = $product;
            }
        }

        // var_dump($data['all']);
        // var_dump($data['suggess']);
        return $data;
    }
    public function getThat() {
        return $this->that;
    }
}

/**
 * Image Process
 */
class ImageProcess
{
    private $config;
    private $that;
    private $upload;
    private $resize;
    function ImageProcess($that) {
        $this->that = $that;
        $this->upload = new UploadProcess($this);
        $this->resize = new ResizeImage($this);
    }
    public function getConfig() {
        return $this->config;
    }
    public function setConfig($config) {
        $this->config = $config;
    }
    public function getThat() {
        return $this->that;
    }
    public function getUpload(){
        return $this->upload;
    }
    public function getResize(){
        return $this->resize;
    }
}

/**
 *  Upload image
 */
interface Upload
{
    public function upload($imageProcess);
}
interface Resize
{
    public function resize($imageProcess);
}

class uploadUserLib implements Upload
{
    public function upload($imageProcess) {
        $imageProcess->getThat()->upload->initialize($imageProcess->getConfig());
        $excute = $imageProcess->getThat()->upload->do_upload('uploadImage');

        // var_dump($imageProcess->that->upload->data());

        return $excute;
    }
}
class resizeUserLib implements Resize
{
    public function resize($imageProcess) {
        $imageProcess->getThat()->load->library("image_lib", $imageProcess->getConfig());
        $imageProcess->getThat()->image_lib->resize();
    }
}

class UploadProcess
{
    private $imageProcess;

    function UploadProcess($imageProcess) {
        $this->imageProcess = $imageProcess;
    }

    public function upload($upload) {
        return $upload->upload($this->imageProcess);
    }
    public function getError() {
        return $this->imageProcess->getThat()->upload->display_errors();
    }
    public function getImage() {
        return $this->imageProcess->getThat()->upload->data();
    }
}

/**
 *  Resize image
 */
class ResizeImage
{
    private $imageProcess;

    function ResizeImage($imageProcess) {
        $this->imageProcess = $imageProcess;
    }

    public function resize($resize) {
        $resize->resize($this->imageProcess);
    }
    public function getConfig() {
        $config = $this->imageProcess->getConfig();
        $image_data = $this->imageProcess->upload->getImage();
        $config['source_image'] = $image_data['full_path'];
        return $config;
    }
}

interface MyName
{
    public function getNewName($ext);
}

/**
 *  Rename file
 */
class getNameByTime implements MyName
{

    // Implements method getNewName
    private $new_name;
    public function getNewName($ext) {
        $this->new_name = time() . $ext;
        return $this->new_name;
    }
    public function getName() {
        return $this->new_name;
    }
}
class Rename
{
    private $image_data;
    private $path;
    private $new_name;
    function Rename($image_data, $path, $getNewName) {
        $this->image_data = $image_data;
        $this->path = $path;
        $this->new_name = $getNewName;
    }

    public function oldPathName() {
        return $this->path . $this->image_data['orig_name'];
    }

    public function newPathName() {
        return $this->path . $this->new_name->getNewName($this->image_data['file_ext']);
    }

    public function excute() {
        rename($this->oldPathName(), $this->newPathName());
    }
    public function getNewName(){
        return $this->new_name;
    }
}

/**
 * Delete Manager
 */
class DeleteMng implements CallFunction
{
    private $that;
    function __construct($that) {
        $this->that = $that;
    }

    public function excute() {
        $idProduct = $this->getIdDelete();
        $this->that->mproduct->delete($idProduct);

        // echo $idProduct;
        $idTran = $this->that->mtransaction->findIdByProductId($idProduct);

        // )
        // if($idTran !=null)
        $this->that->mtransaction->delete($idTran->id);
        redirect('cproduct');
    }
    public function getIdDelete() {
        return $this->that->input->get('id');
    }
    public function getThat(){
        return $this->that;
    }
}

/**
 * Config image
 */
class MyConfig
{
    private $configUpload;
    private $configResize;
    function MyConfig() {
        $this->configUpload['upload_path'] = APPPATH . '../assets/uploads/';
        $this->configUpload['allowed_types'] = 'gif|jpg|png';
        $this->configUpload['max_size'] = '99999';
        $this->configUpload['max_width'] = '1024';
        $this->configUpload['max_height'] = '768';

        // Config to resize
        $this->configResize = array("new_image" => APPPATH . '../assets/uploads/' . "/thumbs", "maintain_ration" => true, "width" => '440', "height" => "440");
    }
    function getConfigUpload() {
        return $this->configUpload;
    }

    function getConfigResize() {
        return $this->configResize;
    }
    function setConfigUpload($config) {
        $this->configUpload = $config;
    }
    function setConfigResize($config) {
        $this->configSize = $config;
    }
}

/**
 *
 */

/* End of file product.php */

/* Location: ./application/controllers/product.php */
