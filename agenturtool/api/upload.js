/**
 * NAS Media Layer — Client-Side Upload / Download Helper
 *
 * Depends on: API_BASE and CSRF being defined (already in index.html).
 *
 * Usage:
 *   const asset = await uploadAsset(file, projectId, {
 *     kind: 'raw',        // 'raw' | 'final'
 *     parentId: null,     // optional: parent asset id
 *     onProgress: pct => console.log(pct + '%')
 *   });
 *
 *   downloadAsset(assetId);   // opens download in current tab
 */

async function uploadAsset(file, projectId, { kind = 'raw', parentId = null, onProgress = null } = {}) {
  // 1 — Create pending asset record
  const prepRes = await fetch(API_BASE + '/nas_assets.php?action=prepare', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type':  'application/json',
      'X-CSRF-Token':  CSRF,
    },
    body: JSON.stringify({
      project_id:   projectId,
      kind,
      filename:     file.name,
      content_type: file.type || 'application/octet-stream',
      ...(parentId ? { parent_id: parentId } : {}),
    }),
  });

  if (!prepRes.ok) {
    const body = await prepRes.json().catch(() => ({}));
    throw new Error(body.error || `Vorbereitung fehlgeschlagen (HTTP ${prepRes.status})`);
  }
  const { data: asset } = await prepRes.json();

  // 2 — Stream file to NAS via PUT (XHR for progress tracking)
  await new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('PUT', API_BASE + '/nas_assets.php?id=' + encodeURIComponent(asset.id), true);
    xhr.withCredentials = true;
    xhr.setRequestHeader('X-CSRF-Token',  CSRF);
    xhr.setRequestHeader('Content-Type',  file.type || 'application/octet-stream');
    xhr.setRequestHeader('Content-Length', String(file.size));

    if (onProgress) {
      xhr.upload.addEventListener('progress', e => {
        if (e.lengthComputable) onProgress(Math.round(e.loaded / e.total * 100));
      });
    }
    xhr.addEventListener('load', () => {
      if (xhr.status < 400) resolve();
      else reject(new Error(`Upload fehlgeschlagen: HTTP ${xhr.status} — ${xhr.responseText.slice(0, 200)}`));
    });
    // Netzwerkfehler bei großen Dateien = meist Server-/Proxy-Limit auf dem
    // Durchleitungsweg. Hinweis auf den direkten NAS-Weg geben.
    const big = file.size > 500 * 1024 * 1024;
    xhr.addEventListener('error', () => reject(new Error(
      'Netzwerkfehler beim Upload' + (big
        ? ' — die Datei ist sehr groß. Bitte direkt auf dem NAS im Ordner ablegen und „↻ Aktualisieren" klicken.'
        : '.')
    )));
    xhr.addEventListener('timeout', () => reject(new Error('Zeitüberschreitung beim Upload')));
    xhr.addEventListener('abort', () => reject(new Error('Upload abgebrochen')));
    xhr.send(file);
  });

  // 3 — Confirm: HEAD-verify on NAS, marks asset as stored
  const confRes = await fetch(
    `${API_BASE}/nas_assets.php?id=${encodeURIComponent(asset.id)}&action=confirm`,
    {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-CSRF-Token': CSRF },
    }
  );
  if (!confRes.ok) {
    const body = await confRes.json().catch(() => ({}));
    throw new Error(body.error || `Bestätigung fehlgeschlagen (HTTP ${confRes.status})`);
  }
  const { data: confirmed } = await confRes.json();
  return confirmed;
}

/**
 * Trigger a browser download for a stored asset.
 * The server streams the file with Content-Disposition: attachment.
 */
function downloadAsset(assetId, projectId = null) {
  // Manuell abgelegte NAS-Dateien (id beginnt mit "nas:") brauchen die project_id
  let url = `${API_BASE}/nas_assets.php?id=${encodeURIComponent(assetId)}`;
  if (projectId) url += `&project_id=${encodeURIComponent(projectId)}`;
  const a = document.createElement('a');
  a.href = url;
  a.download = '';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

/**
 * List all stored assets for a project.
 * Returns an array of asset objects.
 */
async function listAssets(projectId) {
  const res = await fetch(
    `${API_BASE}/nas_assets.php?project_id=${encodeURIComponent(projectId)}`,
    { credentials: 'same-origin' }
  );
  if (!res.ok) {
    const body = await res.json().catch(() => ({}));
    throw new Error(body.error || `Laden fehlgeschlagen (HTTP ${res.status})`);
  }
  const { data } = await res.json();
  return data;
}
