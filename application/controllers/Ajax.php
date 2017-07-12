<?php

/**
 * Created by PhpStorm.
 * User: edgar
 * Date: 2/6/2017
 * Time: 10:19 AM
 */
defined("BASEPATH") OR exit("Akses script tidak diizinkan!");
class Ajax extends CI_Controller {

    public function __construct() {
        parent::__construct();
//        if(!$this->input->is_ajax_request()) {
//            exit("Akses script tidak diizinkan!");
//        }
        $this->load->helper("pengalih");
        proteksi_login($this->session->userdata());
        $this->load->model("Agenda_model","agenda");
        $this->load->model("Pengguna_model","pengguna");
        $this->load->model("Surat_model","surat");
        $this->load->model("Disposisi_model","disposisi");
        header("Content-Type: application/json;charset=utf-8");
    }

    public function tambah_agenda() {
        $deskripsi = $this->input->post("deskripsi");
        header("Content-Type: application/json;charset=utf-8");
        if($this->agenda->buat($deskripsi)) {
            echo json_encode(array("status"=>"ok"));
        } else {
            echo json_encode(array("status"=>"gagal"));
        }
    }

    public function ambil_agenda() {
        $data = $this->agenda->ambil();
        header("Content-Type: application/json;charset=utf-8");
        echo json_encode($data);
    }

    public function check_selesai() {
        $id_agenda = $this->input->post("id_agenda");
        $this->agenda->check_selesai($id_agenda);
    }

    public function hapus_agenda() {
        $id_agenda = $this->input->post("id_agenda");
        $this->agenda->hapus($id_agenda);
    }

    public  function edit_agenda() {
        $post = $this->input->post();
        $this->agenda->edit($post);
    }

    public function edit_data_pengguna() {
        $code = $this->pengguna->edit_profil_pribadi();
        header("Content-Type: application/json;charset=utf-8");
        echo json_encode(array("statusCode" => $code));
    }

    public function update_star() {
        $this->surat->update_star($_POST["id_relasi_pesan"],$_POST["starred"]);
        echo 1;
    }

    public function update_star_disposisi() {
        $this->disposisi->update_star($_POST["id_relasi_disposisi"],$_POST["starred"]);
    }

    public function hitung_surat_per_tanggal() {
        header("Content-Type: application/json;charset=utf-8");
        $dari_tanggal = strtotime("2017-01-01");
        $ke_tanggal = strtotime(date("Y-m-d"));

        $daftar_surat = $this->surat->ambil_surat_per_tanggal();
        $daftar_disposisi = $this->disposisi->ambil_disposisi_per_tanggal();

        echo json_encode(array(
            "surat" => $this->generate_data_per_tanggal($dari_tanggal,$ke_tanggal,$daftar_surat),
            "disposisi" => $this->generate_data_per_tanggal($dari_tanggal,$ke_tanggal,$daftar_disposisi)
        ));
    }

    private function generate_data_per_tanggal($dari_tanggal,$ke_tanggal,$data){
        $arr_days = array();
        $day_passed = ($ke_tanggal - $dari_tanggal); //seconds
        $day_passed = ($day_passed/86400); //days

        $counter = 0;
        $day_to_display = $dari_tanggal;
        while($counter < $day_passed){
            $arr_days[date('Y-m-d',$day_to_display)] = 0;
            $day_to_display += 86400;
            $counter++;
        }
        $arr_days[date("Y-m-d",$ke_tanggal)] = 0;

        $ret = [];

        foreach($data as $row) {
            $arr_days[$row->waktu_kirim] = $row->jumlah;
        }

        foreach($arr_days as $tanggal=>$jumlah) {
            $ret[] = array($tanggal,(int)$jumlah);
        }

        return $ret;
    }

    public function ambil_pemberitahuan() {
        $daftar_pemberitahuan = $this->pemberitahuan->ambil();
        $i = 0;
        foreach($daftar_pemberitahuan as $pemberitahuan) {
            if($pemberitahuan->dibaca == 0)
                $i++;
        }
        echo json_encode(array(
            "data" => $daftar_pemberitahuan,
            "belum_dibaca" => $i
        ));
    }

    public function ambil_data_pengguna() {
        $datauser = $this->session->userdata();
        unset($datauser["password"]);
        echo json_encode(array(
            "data"=>$datauser
        ));
    }

    public function cek_konfig_email() {
        if(isset($_SESSION["config_email"])) {
            echo json_encode(array(
                "status" => true,
                "data" => $_SESSION["config_email"]
            ));
        } else {
            echo json_encode(array(
                "status" => false
            ));
        }
    }

    public function config_email() {
        $posts = $this->input->post();
        $status = true;
        $_SESSION["config_email"] = [];
        foreach($posts as $key=>$post) {
            if($post == ""){
                $status = false;
                unset($_SESSION["config_email"]);
                break;
            } else {

                $_SESSION["config_email"][$key] = $post;
            }
        }
        $config = $_SESSION["config_email"];
        unset($config["password"]);
        echo json_encode(array(
            "status" => $status,
            "data" => $config
        ));
    }

    public function kirim_email() {
        $posts = $this->input->post();
        if(count($posts) == 3) {
            foreach($posts as $key=>$post) {
                if($post == ""){
                    echo json_encode(array(
                        "status" => false,
                        "msg" => $key . " harus diisi"
                    ));
                    exit();
                    break;
                }
            }
            $this->load->library("email");
            $config['protocol'] = "smtp";
//            $config['smtp_host'] = ($_SESSION["config_email"]["tipe"] == "gmail") ? "ssl://smtp.gmail.com" : "smtp.mail.yahoo.com";
            $config['smtp_host'] = "ssl://smtp.gmail.com";
            $config['smtp_port'] = "465";
//            $config['smtp_user'] = $_SESSION["config_email"]["email"];
            $config['smtp_user'] = "edgarpontoh3141@gmail.com";
//            $config['smtp_pass'] = $_SESSION["config_email"]["password"];
            $config['smtp_pass'] = "3141aperture";
            $config['charset'] = "utf-8";
            $config['mailtype'] = "html";
            $config['newline'] = "\r\n";
            $this->email->initialize($config);
            $penerima = explode(",",$posts["penerima"]);

            $this->email->from($_SESSION["config_email"]["email"],"e-Office : " . $this->session->userdata("nama_lengkap"));
            $this->email->subject($posts["subjek"]);
            $this->email->message($posts["isi"]);
            $this->email->to("bnajoan@gmail.com");
            $statusKirim = $this->email->send();
            echo json_encode(array(
                "status" => $statusKirim,
                "data" => ($statusKirim) ? $posts : $this->email->print_debugger()
            ));
        } else {
            echo json_encode(array(
                "status" => false,
                "msg" => "Tidak lengkap"
            ));
        }
    }

    public function unset_konfig_email() {
        unset($_SESSION["config_email"]);
    }

}