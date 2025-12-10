[CCTV Gerbang Masuk]
        |
        |  (LAN IP: 192.168.1.20)
        v
      [Router/Switch]
        |
        |  (LAN IP: 192.168.1.10)
        v
   [Server Web / Backend PHP]
        |
        v
   Ditampilkan di halaman web



   CCTV Masuk  ─────▶│ Server Web   │───▶ Mengirim perintah palang
(192.168.1.20)    │ PHP + AJAX   │     GET http://192.168.1.103/open
                  └──────┬──────┘
                         │
                         ▼
                  ┌──────────────┐
                  │ Mikrokontrol │───▶ Relay ──▶ Motor Palang
                  │ 192.168.1.103│
                  └──────────────┘