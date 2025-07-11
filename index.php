<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cache Redis - Ajout et Requête</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      max-width: 700px;
      margin: 40px auto;
      padding: 0 15px;
      background: #f9f9f9;
      color: #333;
    }
    h1 { text-align: center; }
    form {
      display: flex;
      margin-bottom: 20px;
      gap: 10px;
    }
    input[type="text"] {
      flex: 1;
      padding: 10px;
      font-size: 1rem;
      border: 2px solid #ccc;
      border-radius: 4px;
    }
    button {
      padding: 10px 20px;
      background: #2980b9;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    button:hover { background: #3498db; }
    #loading {
      display: none;
      text-align: center;
      color: #888;
      margin-bottom: 15px;
    }
    #result, #addResult, #keyList {
      padding: 10px;
      background: #fff;
      border-radius: 4px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .success { color: green; font-weight: bold; }
    .error { color: red; }
  </style>
</head>
<body>

<h1>Cache Redis – Requête et Ajout</h1>

<h2>🔍 Rechercher une clé</h2>
<form id="cacheForm">
  <input type="text" id="keyInput" placeholder="Entrez une clé" required />
  <input type="text" id="valueInput" placeholder="Valeur" required />
  <button type="submit">Chercher</button>
</form>


<div id="loading">Chargement…</div>
<div id="result"></div>

<h2>➕ Ajouter une clé (sans système d'expiration)</h2>
<form id="addForm">
  <input type="text" id="newKey" placeholder="Nouvelle clé" required />
  <input type="text" id="newValue" placeholder="Valeur" required />
  <button type="submit">Ajouter</button>
</form>

<div id="addResult"></div>

<h2>📄 Clés stockées dans Redis</h2>
<button id="refreshKeys">Afficher les clés</button>
<div id="keyList"></div>

<script>
  const keyForm = document.getElementById('cacheForm');
  const addForm = document.getElementById('addForm');
  const keyInput = document.getElementById('keyInput');
  const resultDiv = document.getElementById('result');
  const loadingDiv = document.getElementById('loading');
  const addResult = document.getElementById('addResult');
  const refreshBtn = document.getElementById('refreshKeys');
  const keyListDiv = document.getElementById('keyList');

  keyForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const key = keyInput.value.trim();
  const fallbackValue = document.getElementById('valueInput').value.trim();

  if (!key) return;

  resultDiv.innerHTML = '';
  loadingDiv.style.display = 'block';

  try {
    const params = new URLSearchParams({ key });
    if (fallbackValue) params.append('value', fallbackValue);

    const response = await fetch(`redis.php?${params.toString()}`);
    const data = await response.json();

    if (data.error) {
      resultDiv.innerHTML = `<div class="error">${data.error}</div>`;
    } else {
      resultDiv.innerHTML = `
        <div><strong>Source :</strong> <span style="color:${data.source === 'cache' ? 'green' : 'red'}">${data.source}</span></div>
        <div><strong>Donnée :</strong> ${data.data}</div>
        ${data.expires_in !== null ? `<div><strong>Expire dans :</strong> ${data.expires_in} secondes</div>` : ''}
      `;
    }
  } catch (err) {
    resultDiv.textContent = 'Erreur lors de la récupération.';
  } finally {
    loadingDiv.style.display = 'none';
  }
});


  addForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const key = document.getElementById('newKey').value.trim();
    const value = document.getElementById('newValue').value.trim();
    if (!key || !value) return;

    const formData = new FormData();
    formData.append('newKey', key);
    formData.append('newValue', value);

    try {
      const res = await fetch('redis.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      if (data.success) {
        addResult.innerHTML = `<div class="success">${data.message}</div>`;
      } else {
        addResult.innerHTML = `<div class="error">Erreur lors de l’ajout.</div>`;
      }
    } catch (err) {
      addResult.innerHTML = `<div class="error">Erreur réseau ou serveur.</div>`;
    }
  });

  refreshBtn.addEventListener('click', async () => {
    keyListDiv.innerHTML = 'Chargement des clés...';
    try {
      const response = await fetch('redis.php?action=list');
      const data = await response.json();

      if (Object.keys(data).length === 0) {
        keyListDiv.innerHTML = '<em>Aucune clé trouvée.</em>';
        return;
      }

      let html = '<ul>';
          for (const [key, info] of Object.entries(data)) {
            const displayValue = typeof info === 'object' && 'value' in info ? info.value : info;
            const expiresIn = typeof info === 'object' && 'expires_in' in info
            ? info.expires_in !== null
            ? `${info.expires_in} secondes`
            : '<em>Permanent</em>'
          : '';
          html += `<li><strong>${key}</strong> : ${displayValue} 
            ${expiresIn ? `— <small><strong>Expire dans :</strong> ${expiresIn}</small>` : ''}
          </li>`;
        }
      html += '</ul>';
      keyListDiv.innerHTML = html;
    } catch (err) {
      keyListDiv.innerHTML = '<span class="error">Erreur lors du chargement des clés.</span>';
    }
  });
</script>

</body>
</html>
