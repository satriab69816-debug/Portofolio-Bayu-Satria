<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function project_payload(array $p, PDO $pdo): array {
    $stmt = $pdo->prepare('SELECT id, filename, url, sort_order FROM project_images WHERE project_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$p['id']]);
    $images = $stmt->fetchAll();
    return [
        'id' => (string)$p['id'],
        'title' => $p['title'],
        'category' => $p['category'],
        'description' => $p['description'],
        'link' => $p['link'] ?? '',
        'createdAt' => $p['created_at'],
        'images' => array_map(fn($i) => $i['url'], $images),
        'imageIds' => array_map(fn($i) => (int)$i['id'], $images),
    ];
}

$pdo = db();
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id !== null && $id !== '') {
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$id]);
        $p = $stmt->fetch();
        if (!$p) json_response(['ok' => false, 'message' => 'Project tidak ditemukan.'], 404);
        json_response(['ok' => true, 'project' => project_payload($p, $pdo)]);
    }
    $rows = $pdo->query('SELECT * FROM projects ORDER BY created_at DESC, id DESC')->fetchAll();
    json_response(['ok' => true, 'projects' => array_map(fn($p) => project_payload($p, $pdo), $rows)]);
}

require_admin();

if ($method === 'POST') {
    $action = $_POST['action'] ?? 'create';
    if ($action === 'create') {
        $title = trim((string)($_POST['title'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $link = trim((string)($_POST['link'] ?? ''));
        if ($title === '' || $category === '' || $description === '') json_response(['ok'=>false,'message'=>'Nama, kategori, dan deskripsi wajib diisi.'],422);
        if (mb_strlen($title) > 80 || mb_strlen($description) > 500) json_response(['ok'=>false,'message'=>'Judul maksimal 80 karakter dan deskripsi maksimal 500 karakter.'],422);

        $files = $_FILES['images'] ?? null;
        $count = $files && isset($files['name']) ? count($files['name']) : 0;
        if ($count < 1) json_response(['ok'=>false,'message'=>'Minimal 1 foto project.'],422);
        if ($count > MAX_IMAGES_PER_PROJECT) json_response(['ok'=>false,'message'=>'Maksimal 30 foto per project.'],422);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO projects (title, category, description, link) VALUES (?, ?, ?, ?)');
            $stmt->execute([$title, $category, $description, $link]);
            $projectId = (int)$pdo->lastInsertId();
            $dir = dirname(__DIR__) . '/uploads/projects/' . $projectId;
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Folder upload gagal dibuat.');
            $insert = $pdo->prepare('INSERT INTO project_images (project_id, filename, url, sort_order) VALUES (?, ?, ?, ?)');
            for ($i=0; $i<$count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload salah satu foto gagal.');
                if ($files['size'][$i] > MAX_UPLOAD_BYTES) throw new RuntimeException('Maksimal ukuran setiap foto 10 MB.');
                $ext = safe_upload_extension($files['tmp_name'][$i]);
                $filename = bin2hex(random_bytes(12)) . '.' . $ext;
                $target = $dir . '/' . $filename;
                if (!move_uploaded_file($files['tmp_name'][$i], $target)) throw new RuntimeException('Gagal menyimpan foto.');
                $url = 'uploads/projects/' . $projectId . '/' . $filename;
                $insert->execute([$projectId, $filename, $url, $i]);
            }
            $pdo->commit();
            $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?'); $stmt->execute([$projectId]);
            json_response(['ok'=>true,'project'=>project_payload($stmt->fetch(),$pdo)],201);
        } catch (Throwable $e) {
            $pdo->rollBack();
            if (isset($projectId) && isset($dir) && is_dir($dir)) {
                foreach (glob($dir . '/*') ?: [] as $f) @unlink($f);
                @rmdir($dir);
            }
            json_response(['ok'=>false,'message'=>$e->getMessage()],422);
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?'); $stmt->execute([$id]); $project = $stmt->fetch();
        if (!$project) json_response(['ok'=>false,'message'=>'Project tidak ditemukan.'],404);
        $title = trim((string)($_POST['title'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $link = trim((string)($_POST['link'] ?? ''));
        if ($title === '' || $category === '' || $description === '') json_response(['ok'=>false,'message'=>'Nama, kategori, dan deskripsi wajib diisi.'],422);
        $current = $pdo->prepare('SELECT COUNT(*) FROM project_images WHERE project_id = ?'); $current->execute([$id]); $currentCount=(int)$current->fetchColumn();
        $files = $_FILES['images'] ?? null;
        $newCount = $files && isset($files['name']) ? count(array_filter($files['name'], fn($n)=>$n !== '')) : 0;
        if ($currentCount + $newCount > MAX_IMAGES_PER_PROJECT) json_response(['ok'=>false,'message'=>'Maksimal 30 foto per project.'],422);
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE projects SET title=?, category=?, description=?, link=? WHERE id=?')->execute([$title,$category,$description,$link,$id]);
            if ($newCount) {
                $dir = dirname(__DIR__) . '/uploads/projects/' . $id;
                if (!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('Folder upload gagal dibuat.');
                $max = $pdo->prepare('SELECT COALESCE(MAX(sort_order),-1) FROM project_images WHERE project_id=?'); $max->execute([$id]); $sort=(int)$max->fetchColumn()+1;
                $insert=$pdo->prepare('INSERT INTO project_images (project_id, filename, url, sort_order) VALUES (?,?,?,?)');
                for($i=0;$i<count($files['name']);$i++){
                    if($files['name'][$i]==='') continue;
                    if($files['error'][$i]!==UPLOAD_ERR_OK) throw new RuntimeException('Upload foto gagal.');
                    if($files['size'][$i]>MAX_UPLOAD_BYTES) throw new RuntimeException('Maksimal ukuran setiap foto 10 MB.');
                    $ext=safe_upload_extension($files['tmp_name'][$i]); $filename=bin2hex(random_bytes(12)).'.'.$ext; $target=$dir.'/'.$filename;
                    if(!move_uploaded_file($files['tmp_name'][$i],$target)) throw new RuntimeException('Gagal menyimpan foto.');
                    $insert->execute([$id,$filename,'uploads/projects/'.$id.'/'.$filename,$sort++]);
                }
            }
            $pdo->commit();
            $stmt=$pdo->prepare('SELECT * FROM projects WHERE id=?');$stmt->execute([$id]);
            json_response(['ok'=>true,'project'=>project_payload($stmt->fetch(),$pdo)]);
        } catch(Throwable $e){$pdo->rollBack();json_response(['ok'=>false,'message'=>$e->getMessage()],422);}
    }
}

if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $body);
    $action = $_GET['action'] ?? $body['action'] ?? '';
    if ($action === 'delete-image') {
        $imageId=(int)($_GET['image_id'] ?? $body['image_id'] ?? 0);
        $stmt=$pdo->prepare('SELECT * FROM project_images WHERE id=?');$stmt->execute([$imageId]);$img=$stmt->fetch();
        if(!$img) json_response(['ok'=>false,'message'=>'Foto tidak ditemukan.'],404);
        $count=$pdo->prepare('SELECT COUNT(*) FROM project_images WHERE project_id=?');$count->execute([$img['project_id']]);
        if((int)$count->fetchColumn()<=1) json_response(['ok'=>false,'message'=>'Project harus memiliki minimal 1 foto.'],422);
        $pdo->prepare('DELETE FROM project_images WHERE id=?')->execute([$imageId]);
        @unlink(dirname(__DIR__).'/'.$img['url']);
        json_response(['ok'=>true]);
    }
    if ($action === 'delete-project') {
        $id=(int)($_GET['id'] ?? $body['id'] ?? 0);
        $stmt=$pdo->prepare('SELECT * FROM projects WHERE id=?');$stmt->execute([$id]);$p=$stmt->fetch();
        if(!$p) json_response(['ok'=>false,'message'=>'Project tidak ditemukan.'],404);
        $imgs=$pdo->prepare('SELECT url FROM project_images WHERE project_id=?');$imgs->execute([$id]);
        foreach($imgs as $img) @unlink(dirname(__DIR__).'/'.$img['url']);
        $pdo->prepare('DELETE FROM projects WHERE id=?')->execute([$id]);
        $dir=dirname(__DIR__).'/uploads/projects/'.$id; if(is_dir($dir)) @rmdir($dir);
        json_response(['ok'=>true]);
    }
}

json_response(['ok'=>false,'message'=>'Method tidak didukung.'],405);
