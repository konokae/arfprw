// ajax_search.js

async function fetchCSVFiles(csvDir = "data/") {
  // Asumsi: kita sudah tahu daftar file csv, atau bisa pakai API kalau server mendukung.
  // Untuk demo: kita hardcode / fetch daftar manual
  const files = [
    "file1.csv",
    "file2.csv"
    // tambahin sesuai isi folder
  ];

  let results = [];
  for (const file of files) {
    const res = await fetch(csvDir + file);
    const text = await res.text();
    const rows = text.split(/\r?\n/).map(r => r.split(","));
    const headers = rows.shift();

    for (const row of rows) {
      if (row.length === headers.length) {
        const obj = {};
        headers.forEach((h, i) => {
          obj[h] = row[i];
        });
        obj["source_file"] = file;
        results.push(obj);
      }
    }
  }
  return results;
}

function highlight(text, keyword) {
  if (!keyword) return text;
  const words = keyword.trim().split(/\s+/);
  let out = text;
  for (const w of words) {
    const regex = new RegExp("(" + w.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + ")", "gi");
    out = out.replace(regex, "<mark>$1</mark>");
  }
  return out;
}

export async function searchCSV(keyword, page = 1, perPage = 10, onlyCount = false) {
  const allResults = await fetchCSVFiles();
  const matched = [];

  if (keyword) {
    for (const row of allResults) {
      for (const cell of Object.values(row)) {
        if (cell && cell.toLowerCase().includes(keyword.toLowerCase())) {
          matched.push(row);
          break;
        }
      }
    }
  }

  const total = matched.length;
  if (onlyCount) {
    return { total };
  }

  const start = (page - 1) * perPage;
  const paginated = matched.slice(start, start + perPage);

  // bikin HTML sama persis kayak versi PHP
  let html = "";
  if (keyword) {
    html += `<div class="text-center mb-4"><h5>Hasil pencarian untuk: <em>"${keyword}"</em></h5></div>`;
    if (paginated.length > 0) {
      for (const row of paginated) {
        const m = row.source_file.match(/(\d{4}-\d{2}-\d{2})/);
        const tanggal = m ? m[1] : "Tidak diketahui";
        html += `
        <div class="highlight-card">
          Yang anda cari <strong>${highlight(keyword, keyword)}</strong>
          ada di <strong>${highlight(row["Video ID"] ?? "-", keyword)}</strong><br>
          dengan judul <strong>${highlight(row["Video Title"] ?? "-", keyword)}</strong>,
          <strong>${highlight(row["Username"] ?? "-", keyword)}</strong> pada channel
          <strong>${highlight(row["Channel Display Name"] ?? "-", keyword)}</strong><br>
          dengan channel id <strong>${highlight(row["Channel ID"] ?? "-", keyword)}</strong>,
          label <strong>${highlight(row["Asset Labels"] ?? "-", keyword)}</strong>,<br>
          lagu berjudul <strong>${highlight(row["Asset Title"] ?? "-", keyword)}</strong>
          oleh <strong>${highlight(row["Writers"] ?? "-", keyword)}</strong><br>
          📁 <small class="text-muted">Sumber data: ${row.source_file} (${tanggal})</small><br>
          cek di YouTube: <a href="https://www.youtube.com/watch?v=${row["Video ID"]}" target="_blank">
            https://www.youtube.com/watch?v=${row["Video ID"]}
          </a>
        </div>`;
      }
    } else {
      html += `<div class="alert alert-warning text-center">Maaf data tidak ditemukan.</div>`;
    }
  }
  return { total, html };
}
