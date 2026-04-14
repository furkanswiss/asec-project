<?php
// Oturum kontrolü
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Veritabanı bağlantısını db.php'den al
require_once '../db.php';

$mesajlar = $pdo->query('SELECT * FROM mesajlar ORDER BY tarih DESC')->fetchAll();
?>
<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
  <div class="row">
    <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
      <h1>Gelen Mesajlar</h1>
      <style>
        .okundu-row { background: #e9fbe7 !important; }
        .mesajlar-tablo {
          table-layout: fixed;
          width: 100%;
        }
        .mesajlar-tablo th:nth-child(1),
        .mesajlar-tablo td:nth-child(1) {
          width: 5%;
        }
        .mesajlar-tablo th:nth-child(2),
        .mesajlar-tablo td:nth-child(2) {
          width: 20%;
        }
        .mesajlar-tablo th:nth-child(3),
        .mesajlar-tablo td:nth-child(3) {
          width: 50%;
        }
        .mesajlar-tablo th:nth-child(4),
        .mesajlar-tablo td:nth-child(4) {
          width: 15%;
        }
        .mesajlar-tablo th:nth-child(5),
        .mesajlar-tablo td:nth-child(5) {
          width: 10%;
        }
      </style>
      <div class="table-responsive">
        <table class="table table-striped table-bordered bg-white mt-4 mesajlar-tablo">
          <thead class="thead-dark">
            <tr>
              <th></th>
              <th>Ad Soyad</th>
              <th>Konu</th>
              <th>Tarih</th>
              <th>İşlem</th>
            </tr>
          </thead>
          <tbody>
              <?php foreach ($mesajlar as $mesaj): ?>
                  <tr class="<?= (isset($mesaj['okundu']) && $mesaj['okundu'] ? 'okundu-row' : '') . (isset($mesaj['yildiz']) && $mesaj['yildiz'] ? ' yildizli-row' : '') ?>">
                      <td style="text-align:center; cursor:pointer;" class="yildiz-td" data-id="<?= $mesaj['id'] ?>">
                          <i class="fa<?= isset($mesaj['yildiz']) && $mesaj['yildiz'] ? 's' : 'r' ?> fa-star yildiz-icon" style="color: #f1c40f;"></i>
                      </td>
                      <td><?= htmlspecialchars($mesaj['ad']) ?></td>
                      <td class="text-truncate" title="<?= htmlspecialchars($mesaj['konu']) ?>"><?= htmlspecialchars($mesaj['konu']) ?></td>
                      <td><?= htmlspecialchars($mesaj['tarih']) ?></td>
                      <td>
                          <button class="btn btn-info btn-sm goruntule-btn" 
                                  data-id="<?= $mesaj['id'] ?>"
                                  data-ad="<?= htmlspecialchars($mesaj['ad'], ENT_QUOTES) ?>"
                                  data-email="<?= htmlspecialchars($mesaj['email'], ENT_QUOTES) ?>"
                                  data-konu="<?= htmlspecialchars($mesaj['konu'], ENT_QUOTES) ?>"
                                  data-mesaj="<?= htmlspecialchars($mesaj['mesaj'], ENT_QUOTES) ?>"
                                  data-tarih="<?= htmlspecialchars($mesaj['tarih']) ?>"
                                  data-ip="<?= htmlspecialchars($mesaj['ip'] ?? '', ENT_QUOTES) ?>"
                                  data-toggle="modal" 
                                  data-target="#mesajModal">
                              <i class="fas fa-eye"></i> Görüntüle
                          </button>
                      </td>
                  </tr>
              <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mesaj Detay Modal -->
      <div class="modal fade" id="mesajModal" tabindex="-1" role="dialog" aria-labelledby="mesajModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="mesajModalLabel">Mesaj Detayları</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>ID:</strong>
                  <p id="modal-id" class="mb-0"></p>
                </div>
                <div class="col-md-6">
                  <strong>Tarih:</strong>
                  <p id="modal-tarih" class="mb-0"></p>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Gönderen:</strong>
                  <p id="modal-ad" class="mb-0"></p>
                </div>
                <div class="col-md-6">
                  <strong>E-posta:</strong>
                  <p id="modal-email" class="mb-0"></p>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-12">
                  <strong>IP Adresi:</strong>
                  <p id="modal-ip" class="mb-0"></p>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-12">
                  <strong>Konu:</strong>
                  <p id="modal-konu" class="mb-0"></p>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12">
                  <strong>Mesaj İçeriği:</strong>
                  <div id="modal-mesaj" class="border p-3 mt-2" style="background-color: #f8f9fa; border-radius: 4px; white-space: pre-wrap; word-wrap: break-word;"></div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-success okundu-btn-modal" id="modal-okundu-btn"><i class="fas fa-check"></i> Okundu İşaretle</button>
              <button type="button" class="btn btn-danger sil-btn-modal" id="modal-sil-btn"><i class="fas fa-trash"></i> Sil</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
            </div>
          </div>
        </div>
      </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
        <script>
        $(function(){
            $('.yildiz-td').click(function(){
                var id = $(this).data('id');
                $.post('mesaj_aksiyon.php', {id: id, action: 'yildiz'}, function(resp){
                    location.reload();
                });
            });
            
            var currentMessageId = null;
            
            // Modal popülasyonu
            $('#mesajModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var ad = button.data('ad');
                var email = button.data('email');
                var konu = button.data('konu');
                var mesaj = button.data('mesaj');
                var tarih = button.data('tarih');
                var ip = button.data('ip') || 'N/A';
                
                currentMessageId = id;
                
                var modal = $(this);
                modal.find('#modal-id').text(id);
                modal.find('#modal-ad').text(ad);
                modal.find('#modal-email').text(email);
                modal.find('#modal-konu').text(konu);
                modal.find('#modal-mesaj').text(mesaj);
                modal.find('#modal-tarih').text(tarih);
                modal.find('#modal-ip').text(ip);
            });
            
            // Modal içindeki action butonları
            $('#modal-okundu-btn').click(function(){
                if(!currentMessageId) return;
                $.post('mesaj_aksiyon.php', {id: currentMessageId, action: 'okundu'}, function(resp){
                    $('#mesajModal').modal('hide');
                    location.reload();
                });
            });
            
            $('#modal-sil-btn').click(function(){
                if(!currentMessageId) return;
                if(!confirm('Silmek istediğinize emin misiniz?')) return;
                $.post('mesaj_aksiyon.php', {id: currentMessageId, action: 'sil'}, function(resp){
                    $('#mesajModal').modal('hide');
                    location.reload();
                });
            });
        });
        </script>
    </div>
    </div>
  </div>
</main>
</body>
</html>
