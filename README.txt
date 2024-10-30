=== Jubelio Shipment: Plugin Ongkos Kirim Instant, Sameday, Regular, Cargo ===
Contributors: jubelio
Tags:  Grab Express, GoSend, JNE, Si Cepat, SAP Express, Anteraja, Paxel, Ninja, RPX, Lion Parcel, Ninja
Requires at least: 6.5
Tested up to: 6.5.4
Stable tag: 1.8.2
Requires PHP: 7.4 or higher
WC requires at least: 9.0.x
WC tested up to: 9.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin WooCommerce yang simpel dan cepat dengan beragam pilihan kurir seperti Grab Express, JNE, SiCepat, Paxel, dan masih banyak lagi.

== Penjelasan Singkat ==

Jubelio Shipment menyediakan plugin untuk WooCommerce yang dapat mengintegrasikan website dengan jasa pengiriman di Indonesia.

Bekerjasama dengan lebih dari 10 kurir besar di Indonesia dengan mendukung layanan kurir instant.

== Fitur ==

* Kalkulasi total ongkos kirim di halaman check-out
* Bebas pilih kurir yang ingin kamu aktifkan
* Simpel pemilihan alamat, cukup ketik kodepos atau Kecamatan atau Kota, alamat langsung terisi otomatis.
* Pinpoint Location dari Google Maps.
* Menambahkan Alamat Lengkap, untuk informasi ke kurir. Jangan sampai mereka salah kirim.

== Kurir Yang Tersedia ==

* Grab Express – Instant, Same Day.
* GoSend - Instant, Same Day.
* JNE – REG, YES (Yakin Esok Sampai), OKE, SS, Trucking.
* SiCepat – SUINT, Reguler, Gokil, BEST.
* SAP Express - One Day, Same Day, Regular, Cargo.
* Lion Parcel – Reg Pack, Jago Pack.
* AnterAja – Reguler, Same Day, Next Day.
* Paxel – Instant, Same Day, Cargo.
* Ninja - Standard.
* Kerry Express - Regular.
* ID Express - Regular.
* ONDELIVERY - Regular, Sama Day, Next Day, Cargo.

== Kenapa Harus Jubelio Shipment? ==

Jubelio Shipment memiliki lebih dari 8 pilihan kurir besar di Indonesia. Selain itu, Jubelio Shipment menyediakan layanan pengiriman instant.

== Installation ==

Instalasi plugin Jubelio Shipment mudah dan kurang dari 5 menit. Berikut caranya:

1. Download dan activate plugin Jubelio Shipment.
2. Atur Client ID, dan Client Secret, ini perlu kamu lakukan jika sudah mendaftar Jubelio Shipment.
3. Atur Webstore ID (Optional), tidak perlu diset jika kamu tidak berlangganan Jubelio Omnichannel.
4. Masukan Google Map API Jika kamu ingin menggunakan layanan instant.
5. Pilih apakah perlu aktifkan Asuransi Pengriman,
6. Pilih apakah perlu aktifkan Payment Voucher.
7. Pilih apakah perlu aktifkan Multi Origin.
8. Klik “Save Changes”.
9. Masuk ke Shipping Zone, tambahkan Jubelio Shipment.
10. Atur alamat asal (Origin).
11. Atur berat isi asal (Base weight).
12. Pilih Kurir yang ingin kamu gunakan.

Jika kamu membutuhkan bantuan, bisa kontak ke info@jubelio.com

== Frequently Asked Questions ==

= Apakah plugin ini gratis? =

Ya, untuk starter-plan. Jika Anda ingin menggunakan fitur Auto Resi, Payment Voucher, dan Multi Origin, maka Anda harus berlangganan Jubelio. Keterangan lebih lanjut ada di <a href="https://jubelio.com/" target="_blank">halaman ini</a>.

= Bisakah saya menonaktifkan kurir yang tidak ingin saya pakai? =

Ya tentu, Anda bisa mengaktifkan beberapa kurir yang sejalan dengan keinginan dan bisnis anda.

= Apakah ongkos kirim yang tercantum adalah ongkos kirim terbaru? =

Ya, karena Jubelio Shipment terintegrasi langsung dengan perusahaan ekspedisi yang tercantum di kolom deskripsi. Jadi jika ada perubahan dari pihak ekspedisi, maka akan secara otomatis ter-update.

= Apakah plugin ini kompatibel untuk website saya? =

Ya, plugin Jubelio Shipment kompatibel untuk penggunaan di Wordpress dan WooCommerce.

= Apakah plugin ini bisa melakukan pengiriman ke luar negeri? =

Mohon maaf untuk saat ini plugin Jubelio Shipment hanya mendukung pengiriman di wilayah Indonesia saja.

= Apakah plugin ini mendukung berbagai bahasa? =

Ya, namun saat ini plugin Jubelio Shipment baru mendukung dua bahasa yakni bahasa Indonesia dan bahasa Inggris.

== Changelog ==
= 1.8.2 =
* Fix - fix shipping coordinate
= 1.8.1 =
* Fix - fix shipping insurance
= 1.8.0 =
* Update - Make shipping insurance active by default
* Delete - Delete unused code
= 1.7.9 =
* Fix - Curl error when load courier in backend and frontend
* Add - Increase curl load
* Add - Add caching when call shipment api
= 1.7.8 =
* Fix - ETA for sameday or instant courier
* Add - Required woocommerce at least version 9.0.0
* Fix - Fix some bugs and remove multiorigin option
= 1.7.6 =
* Fix - Shipping ETA
* Add - Add feature COD
= 1.7.4 =
* Fix - Update missing js file
= 1.7.3 =
* Update - Update grabmaps with iframe technology
* Remove - Remove shipping Voucher
* Fix - Fix shipping bugs
= 1.7.1 =
* Fix - Remove cod payment method.
= 1.7.0 =
* Update - Jubelio Shipment has now been tested up to WooCommerce 8.4 or higher
* Update - Jubelio Shipment ETA
* Update - Jubelio Shipment Pinpoint Location label
= 1.6.9 =
* Fix grabmap bugs in API
= 1.6.8 =
* Fix grabmap bugs
= 1.6.7 =
* Add whitelist API
= 1.6.6 =
* Change API url Grabmaps
= 1.6.5 =
* Fix selected courier when first load checkout page.
* Fix some bugs.
* Fix insurance in guest mode.
* Change googlemaps with Grabmaps.
* Add check all courier in admin.
* Change courier list view in admin.
= 1.6.4 =
* Enable button purchase order when there is one courier available.
* Fixed total price when there is one courier available.
* Automatically selected courier if there is one courier available.
* Add additional validate promotion function before checkout.
= 1.6.3 =
* Fix promotion issue.
= 1.6.2 =
* Fix issue when chosen shipping address.
= 1.6.1 =
* Fix some issue.
= 1.6.0 =
* Adding courier GoSend Instant, Sameday.
* Fix bug shipping promotion.
* Fix some issue.
= 1.5.4 =
* Fix some issue.
= 1.5.3 =
* Fix bug wrong shipping label
= 1.5.2 =
* Fix some issue.
= 1.5.1 =
* Shipping Promotion.
* Fix bug meta for shipping promotion.
* Compatible with plugin Shipping Discount (https://wordpress.org/plugins/shipping-discount/).
= 1.5.0 =
* Auto whitelist to Google MAP.
* Add notice in admin panel when invalid client_id or client_secret.
* Fix bug API Response.
= 1.4.1 =
Fix bug in feature experiment.
= 1.4.0 =
* Change client_id type from integer to string.
* Adding several helper function.
* Add new hook function 'jubelio_shipment_rates_debug'.
* Adding shipping promotion (Experiment Feature).
* Auto Whitelist Domain for Google API (Experiment Feature).
* Adding new value of languages for shipping promotion.
= 1.3.0 =
* Fix bugs null object or null array.
* Adding new hook function 'jubelio_shipment_token_hardcoded', 'jubelio_shipment_api_url', 'jubelio_shipment_get_shipping_rate'
= 1.2.2 =
* Fix bugs
= 1.2.1 =
* Fix typo meta key
= 1.2.0 =
* Adding default value for district and subdistrict meta, extract from address.
* Moving some anonymous function to helpers.
* Fix some bugs.
= 1.1.3 =
* Fix bugs variable unset in wp-admin.
= 1.1.2 =
* Fix bugs variable unset.
= 1.1.1 =
* Reorder Shipping Email and Shipping Phone to last priority.
= 1.1.0 =
* Adding Shipping Email and Shipping Phone Fields in Checkout Page.
= 1.0.3 =
* Fix bugs shipping insurance value in Jubelio Omni Channel.
= 1.0.2 =
* Add meta data _is_jubelio_shipment_exists in Flat Rate, Free Shipping and Local Pickup. Special case for Jubelio Omni Channel.
= 1.0.1 =
* Fix bugs in calculate shipping,
* Fix wrong logic when showing shipping insurance.
= 1.0 =
* Initial release

= Demo =

Please visit the link below for the live demo:

[https://jubeliofashion.jubelio.store](https://jubeliofashion.jubelio.store)
