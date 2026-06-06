<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Landing & Auth
$routes->get('/', 'Auth::landing');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::loginProcess');
$routes->get('logout', 'Auth::logout');

$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);

// Admin module
$routes->group('', ['filter' => 'role:admin'], static function ($routes) {
	// Kelola akun admin
	$routes->get('admin/akun', 'Akun::index');
	$routes->get('admin/akun/tambah', 'Akun::tambah');
	$routes->post('admin/akun/simpan', 'Akun::simpan');
	$routes->get('admin/akun/edit/(:segment)/(:num)', 'Akun::edit/$1/$2');
	$routes->post('admin/akun/update/(:segment)/(:num)', 'Akun::update/$1/$2');
	$routes->post('admin/akun/hapus/(:segment)/(:num)', 'Akun::hapus/$1/$2');

	// Kelola siswa + registrasi wajah / RFID
	$routes->get('siswa', 'Siswa::index');
	$routes->get('siswa/data', 'Siswa::data');
	$routes->get('siswa/landmark/(:num)', 'Siswa::landmark/$1');
	$routes->get('siswa/tambah', 'Siswa::tambah');
	$routes->post('siswa/simpan', 'Siswa::simpan');
	$routes->get('siswa/edit/(:num)', 'Siswa::edit/$1');
	$routes->post('siswa/update/(:num)', 'Siswa::update/$1');
	$routes->get('siswa/hapus/(:num)', 'Siswa::hapus/$1');

	// Kelola data guru (monitoring)
	$routes->get('guru', 'Guru::index');
	$routes->get('guru/tambah', 'Guru::tambah');
	$routes->post('guru/simpan', 'Guru::simpan');
	$routes->get('guru/edit/(:num)', 'Guru::edit/$1');
	$routes->post('guru/update/(:num)', 'Guru::update/$1');
	$routes->get('guru/hapus/(:num)', 'Guru::hapus/$1');

	// Registrasi wajah dan RFID siswa/guru
	$routes->get('admin/registrasi', 'Registrasi::index');
	$routes->post('admin/registrasi/mulai', 'Registrasi::mulaiModeRegis');
	$routes->post('admin/registrasi/simpan', 'Registrasi::simpan');
	$routes->get('admin/registrasi/sesi/(:num)', 'Registrasi::statusSesi/$1');
	$routes->post('admin/registrasi/sesi/(:num)/batal', 'Registrasi::batalSesi/$1');
	$routes->get('admin/registrasi/pemetaan', 'Registrasi::pemetaan');
	$routes->post('admin/registrasi/pemetaan/simpan', 'Registrasi::simpanPemetaan');

	// Kelola jadwal
	$routes->get('jadwal', 'Jadwal::index');
	$routes->get('jadwal/tambah', 'Jadwal::tambah');
	$routes->post('jadwal/simpan', 'Jadwal::simpan');
	$routes->get('jadwal/edit/(:num)', 'Jadwal::edit/$1');
	$routes->post('jadwal/update/(:num)', 'Jadwal::update/$1');
	$routes->get('jadwal/hapus/(:num)', 'Jadwal::hapus/$1');

	// Master data (kelas)
	$routes->get('master-data/kelas', 'MasterKelas::index');
	$routes->post('master-data/kelas/simpan', 'MasterKelas::simpan');
	$routes->post('master-data/kelas/update/(:num)', 'MasterKelas::update/$1');
	$routes->get('master-data/kelas/hapus/(:num)', 'MasterKelas::hapus/$1');
});

// Presensi mutate admin-only
$routes->group('', ['filter' => 'role:admin'], static function ($routes) {
	$routes->post('presensi/simpan', 'Presensi::simpan');
	$routes->post('presensi/manual', 'Presensi::manual');
	$routes->post('presensi/update/(:num)', 'Presensi::update/$1');
	$routes->post('presensi/hapus/(:num)', 'Presensi::hapus/$1');
});

// Laporan dapat diakses guru dan admin
$routes->group('', ['filter' => 'role:admin,guru'], static function ($routes) {
	$routes->get('presensi', 'Presensi::index');
	$routes->get('presensi/riwayat', 'Presensi::riwayat');
	$routes->get('presensi/cetak', 'Presensi::cetak');
});
