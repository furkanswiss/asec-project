<?php
require_once 'includes/config.php';

// Oturum kontrolü
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Silme işlemi
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM blog_posts WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $id);
        if(mysqli_stmt_execute($stmt)){
            header("location: blog-yonetim.php?success=1");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// Blog yazılarını getir
$sql = "SELECT * FROM blog_posts ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<?php include 'admin-header.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="container-fluid">
    <div class="row">
        <div class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Blog Yazıları</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="blog-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Yeni Blog Yazısı
                    </a>
                </div>
            </div>

            <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                İşlem başarıyla tamamlandı!
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col" width="80">#</th>
                            <th scope="col" width="120">Görsel</th>
                            <th scope="col">Başlık</th>
                            <th scope="col" width="120">Tarih</th>
                            <th scope="col" width="150">Kategori</th>
                            <th scope="col" width="150">Yazar</th>
                            <th scope="col" width="150">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php $sira = 1; ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $sira++; ?></td>
                                    <td>
                                        <?php if($row['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($row['image_url']); ?>" class="img-thumbnail" alt="Blog Görseli" style="width: 80px; height: 60px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="text-center"><i class="fas fa-image text-muted"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                        <?php if(isset($row['content']) && !empty($row['content'])): ?>
                                            <div class="small text-muted"><?php echo mb_substr(strip_tags($row['content']), 0, 100) . '...'; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d.m.Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <?php 
                                        echo isset($row['category']) ? htmlspecialchars($row['category']) : '<span class="text-muted">Belirtilmemiş</span>'; 
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo isset($row['author']) ? htmlspecialchars($row['author']) : '<span class="text-muted">Belirtilmemiş</span>'; 
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../blog-detay.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" target="_blank" title="Görüntüle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="blog-duzenle.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="deleteBlog(<?php echo $row['id']; ?>)" class="btn btn-sm btn-danger" title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="alert alert-info mb-0" role="alert">
                                        <i class="fas fa-info-circle mr-2"></i> Henüz blog yazısı bulunmamaktadır.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteBlog(id) {
            if(confirm('Bu blog yazısını silmek istediğinizden emin misiniz?')) {
                window.location.href = 'blog-yonetim.php?delete=' + id;
            }
        }
    </script>
</body>
</html> 