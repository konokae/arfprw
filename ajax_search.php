<?php
$csvDir = __DIR__ . '/data';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$onlyCount = isset($_GET['count']) && $_GET['count'] == '1';

function highlight($text, $keyword) {
  $keywords = array_filter(preg_split('/\\s+/', $keyword));
  foreach ($keywords as $kw) {
    $text = preg_replace("/(" . preg_quote($kw, '/') . ")/i", '<mark>$1</mark>', $text);
  }
  return $text;
}

$results = [];

if ($search) {
  foreach (scandir($csvDir) as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) !== 'csv') continue;
    $path = $csvDir . '/' . $file;
    if (!file_exists($path)) continue;

    $handle = fopen($path, 'r');
    $headers = fgetcsv($handle);
    while (($row = fgetcsv($handle, 10000, ',')) !== FALSE) {
      if (count($row) === count($headers)) {
        $rowAssoc = array_combine($headers, $row);
        $rowAssoc['source_file'] = $file;
        foreach ($rowAssoc as $cell) {
          if (stripos($cell, $search) !== false) {
            $results[] = $rowAssoc;
            break;
          }
        }
      }
    }
    fclose($handle);
  }
}

$total = count($results);
if ($onlyCount) {
  header('Content-Type: application/json');
  echo json_encode(['total' => $total]);
  exit;
}

$start = ($page - 1) * $perPage;
$paginatedResults = array_slice($results, $start, $perPage);
?>

<?php if ($search): ?>
  <div class="text-center mb-4"><h5>Hasil pencarian untuk: <em>"<?= htmlspecialchars($search) ?>"</em></h5></div>

  <?php if (count($paginatedResults) > 0): ?>
    <?php foreach ($paginatedResults as $rowAssoc): ?>
      <?php
        preg_match('/(\\d{4}-\\d{2}-\\d{2})/', $rowAssoc['source_file'] ?? '', $m);
        $tanggal = $m[1] ?? 'Tidak diketahui';
      ?>
      <div class="highlight-card">
        Yang anda cari <strong><?= highlight(htmlspecialchars($search), $search) ?></strong>
        ada di <strong><?= highlight(htmlspecialchars($rowAssoc['Video ID'] ?? '-'), $search) ?></strong><br>
        dengan judul <strong><?= highlight(htmlspecialchars($rowAssoc['Video Title'] ?? '-'), $search) ?></strong>,
        <strong><?= highlight(htmlspecialchars($rowAssoc['Username'] ?? '-'), $search) ?></strong> pada channel
        <strong><?= highlight(htmlspecialchars($rowAssoc['Channel Display Name'] ?? '-'), $search) ?></strong><br>
        dengan channel id <strong><?= highlight(htmlspecialchars($rowAssoc['Channel ID'] ?? '-'), $search) ?></strong>,
        label <strong><?= highlight(htmlspecialchars($rowAssoc['Asset Labels'] ?? '-'), $search) ?></strong>,<br>
        lagu berjudul <strong><?= highlight(htmlspecialchars($rowAssoc['Asset Title'] ?? '-'), $search) ?></strong>
        oleh <strong><?= highlight(htmlspecialchars($rowAssoc['Writers'] ?? '-'), $search) ?></strong><br>
        📁 <small class="text-muted">Sumber data: <?= htmlspecialchars($rowAssoc['source_file'] ?? '-') ?> (<?= htmlspecialchars($tanggal) ?>)</small><br>
        cek di YouTube: <a href="https://www.youtube.com/watch?v=<?= htmlspecialchars($rowAssoc['Video ID'] ?? '-') ?>" target="_blank">
          https://www.youtube.com/watch?v=<?= htmlspecialchars($rowAssoc['Video ID'] ?? '-') ?>
        </a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="alert alert-warning text-center">Maaf data tidak ditemukan.</div>
  <?php endif; ?>
<?php endif; ?>
