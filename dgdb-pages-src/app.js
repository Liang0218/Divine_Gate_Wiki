const root = document.querySelector("#app");
const elementNames = { 0: "一般", 1: "無", 2: "火", 3: "水", 4: "光", 5: "闇", 6: "風", 7: "回復" };
const raceNames = { 0: "無種族", 1: "人類", 2: "龍", 3: "神", 4: "魔物", 5: "妖精", 6: "獸", 7: "機械", 8: "強化合成" };
const skillNames = { n: "普通技能", a: "主動技能", l: "隊長技能", p: "被動技能", ln: "Link 技能", lp: "Link 被動技能" };
let db;
const dataCache = new Map();
const unitPageSize = 60;
let routeGeneration = 0;
const battleStorageKey = "dg-wiki-native-battle-v3";
const battleViewStorageKey = `${battleStorageKey}:view`;
const battleFormat = "dg-battle-presentation";

const esc = (value) => String(value ?? "").replace(/[&<>"']/g, (char) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[char]);
const id3 = (value) => String(value ?? "").padStart(3, "0");
const unitHref = (unit) => `/unit/${encodeURIComponent(unit.draw_id)}`;
const areaHref = (area) => `/area/${encodeURIComponent(area.fix_id)}`;
const questHref = (quest) => `/quest/${encodeURIComponent(quest.fix_id)}`;
const unitByDraw = (drawId) => db.units?.find((unit) => String(unit.draw_id) === String(drawId));
const unitImage = (unit, css = "") => unit ? `<img class="${css}" src="/img/icon/${id3(unit.draw_id)}-icon.png" alt="No.${esc(id3(unit.draw_id))}" loading="lazy" onerror="this.hidden=true">` : "";
const unitArtwork = (unit) => unit ? `<img class="portrait-art" src="/img/large/${id3(unit.draw_id)}.png" alt="${esc(unit.name)} \u7acb\u7e6a" loading="eager" onerror="this.onerror=null;this.src='/img/icon/${id3(unit.draw_id)}-icon.png';this.classList.add('portrait-art--fallback')">` : "";
const unitLink = (unit, options = {}) => unit ? `<a class="unit-card" href="${unitHref(unit)}">${unitImage(unit)}<span><strong>${esc(options.rank ? `${options.rank}. ` : "")}${esc(unit.name)}</strong><small>No.${esc(id3(unit.draw_id))} · ${esc(elementNames[unit.element] || "屬性未記錄")}</small></span></a>` : "";
const unitCards = (list) => list.length ? `<div class="unit-grid">${list.map((unit) => unitLink(unit)).join("")}</div>` : `<p class="empty">沒有符合條件的資料。</p>`;
const section = (title, content) => `<section class="panel"><h2 class="section-title">${esc(title)}</h2>${content}</section>`;
const rowTable = (rows) => `<table class="data-table"><tbody>${rows.filter(([, value]) => value !== null && value !== undefined && value !== "" && value !== 0).map(([label, value]) => `<tr><th>${esc(label)}</th><td>${esc(value)}</td></tr>`).join("")}</tbody></table>`;
const gameMarkupPattern = /\[\[(\d+)\|([^\]]+)\]\]|\[([a-f\d]{6}|www)\]|\[\-\]/gi;

function plainGameText(value) {
  return String(value ?? "").replace(/\[\[(\d+)\|([^\]]+)\]\]/g, "$2").replace(/\[([a-f\d]{6}|www)\]|\[\-\]/gi, "");
}

function formatGameText(value, linkKind = null) {
  const text = String(value ?? "");
  let output = "";
  let cursor = 0;
  let openColors = 0;
  for (const match of text.matchAll(gameMarkupPattern)) {
    output += esc(text.slice(cursor, match.index));
    if (match[1]) {
      const href = linkKind === "quest" ? `/quest/${encodeURIComponent(match[1])}` : `/unit/${encodeURIComponent(match[1])}`;
      const label = formatGameText(match[2]);
      output += linkKind ? `<a href="${href}">${label}</a>` : label;
    } else if (match[3]) {
      const color = match[3].toLowerCase() === "www" ? "#fff" : `#${match[3]}`;
      output += `<span class="game-color" style="color:${color}">`;
      openColors++;
    } else if (openColors > 0) {
      output += "</span>";
      openColors--;
    }
    cursor = match.index + match[0].length;
  }
  output += esc(text.slice(cursor));
  return output + "</span>".repeat(openColors);
}

function shell(title, lede, content) {
  document.title = `${plainGameText(title)} - Divine Gate 資料庫`;
  root.innerHTML = `<h1 class="page-title">${formatGameText(title)}</h1>${lede ? `<p class="page-lede">${esc(plainGameText(lede))}</p>` : ""}${content}`;
}

function renderHome() {
  document.title = "Divine Gate 資料庫";
  root.innerHTML = `<section class="hero"><div class="eyebrow"><span class="site-version-badge">v1.1.1</span> Historical archive ? 2017.09.21</div><h1>Divine Gate<br>資料庫典藏</h1><p>以原 dgdb 資料庫快照重建的唯讀典藏站。瀏覽角色能力、技能資料、關卡與故事紀錄。</p><form class="searchbar" id="home-search"><input id="home-query" autocomplete="off" placeholder="搜尋角色名稱或編號，例如 001"><button>搜尋角色</button></form></section><div class="stat-grid"><div class="stat-card"><strong>${db.unitCount.toLocaleString()}</strong>角色資料</div><div class="stat-card"><strong>${db.areaCount.toLocaleString()}</strong>區域</div><div class="stat-card"><strong>${db.questCount.toLocaleString()}</strong>關卡</div></div><div class="link-grid"><a class="link-card" href="/unitlist"><strong>角色列表 →</strong><span>依名稱、編號、屬性搜尋角色</span></a><a class="link-card" href="/questlist"><strong>關卡資料 →</strong><span>依區域瀏覽關卡和樓層資訊</span></a><a class="link-card" href="/skill/n"><strong>技能一覽 →</strong><span>檢視普通、主動、隊長及 Link 技能</span></a><a class="link-card" href="/rank"><strong>玩家等級 →</strong><span>查看等級經驗、體力和隊伍成本</span></a><a class="link-card" href="/story"><strong>故事文本 →</strong><span>瀏覽快照內收錄的翻譯文本</span></a><a class="link-card" href="/battle"><strong>戰鬥配置工具 →</strong><span>在原生五格角色框內調整立繪取景，匯出 APK 調整檔</span></a></div><p class="snapshot-note">此站內容來自 2017-09-21 的資料快照。原站需要即時資料庫的功能已改為本地靜態瀏覽。</p>`;
  document.querySelector("#home-search").addEventListener("submit", (event) => {
    event.preventDefault();
    const query = document.querySelector("#home-query").value.trim();
    navigate(`/unitlist?q=${encodeURIComponent(query)}`);
  });
}

function renderBattleTool() {
  const units = db.units || [];
  const scene = db.scene;
  const nativeUnits = scene.catalog.units;
  const wikiByDrawId = new Map(units.map((unit) => [Number(unit.draw_id), unit]));
  const available = nativeUnits.filter((unit) => wikiByDrawId.has(Number(unit.drawId)));
  const availableById = new Map(available.map((unit) => [Number(unit.id), unit]));
  const enemyChoices = available.filter((unit) => unit.enemies?.length);
  const enemyById = new Map(enemyChoices.map((unit) => [Number(unit.id), unit]));
  const slotLabels = ["隊長 · 右側大格", "隊員 1 · 右上", "隊員 2 · 右下", "隊員 3 · 左上", "好友 · 左下"];
  const nativeFields = ["img_0_tiling", "img_0_offsetX", "img_0_offsetY", "img_1_tiling", "img_1_offsetX", "img_1_offsetY", "img_2_tiling", "img_2_offsetX", "img_2_offsetY", "img_3_tiling", "img_3_offsetX", "img_3_offsetY", "img_4_tiling", "img_4_offsetX", "img_4_offsetY", "img_cutin_tiling", "img_cutin_offsetX", "img_cutin_offsetY", "size_width", "size_height", "pivot", "side_offset"];
  const enemyFieldNames = ["pos_absolute", "posx_offset", "posy_offset", "posz_value", "hp_gauge_type", "hp_posx_offset", "hp_posy_offset"];
  const emptyEnemyOverrides = () => ({ pos_absolute: 2, posx_offset: 0, posy_offset: 0 });
  const validEnemyOverrides = (overrides) => overrides && Object.entries(overrides).every(([key, value]) => enemyFieldNames.includes(key) && Number.isInteger(value)
    && (key === "pos_absolute" ? value >= 0 && value <= 2 : key === "hp_gauge_type" ? value >= 0 && value <= 4 : value >= -2000 && value <= 2000));
  const validVisualOverrides = (overrides, keys) => overrides && Object.entries(overrides).every(([key, value]) => keys.includes(key) && Number.isInteger(value));
  const nativeDefaultSlots = () => available.slice(0, 5).map((unit) => ({ unitId: Number(unit.id), overrides: {} }));
  const partyProfileKey = (slot, unitId) => `${slot}:${unitId}`;
  const validPartyProfiles = (profiles) => profiles == null || (typeof profiles === "object" && !Array.isArray(profiles)
    && Object.entries(profiles).every(([key, overrides]) => {
      const [slot, unitId] = key.split(":").map(Number);
      return Number.isInteger(slot) && slot >= 0 && slot < 5 && availableById.has(unitId) && validVisualOverrides(overrides, nativeFields);
    }));
  const defaultEnemy = enemyChoices[0];
  const validDraft = (value) => value?.format === "dg-wiki-native-battle-draft" && value.dataVersion === scene.catalog.dataVersion
    && value.sourceApkSha256 === scene.catalog.sourceApkSha256 && Array.isArray(value.party) && value.party.length === 5
    && value.party.every((slot) => availableById.has(Number(slot.unitId)) && validVisualOverrides(slot.overrides, nativeFields))
    && validPartyProfiles(value.partyProfiles)
    && availableById.has(Number(value.cutin?.unitId)) && validVisualOverrides(value.cutin?.overrides, ["img_cutin_tiling", "img_cutin_offsetX", "img_cutin_offsetY"])
    && enemyById.has(Number(value.enemy?.unitId)) && validVisualOverrides(value.enemy?.fieldOverrides, ["size_width", "size_height", "pivot", "side_offset"])
    && validEnemyOverrides(value.enemy?.overrides);
  let party = nativeDefaultSlots();
  let partyProfiles = {};
  let cutin = { unitId: Number(party[0]?.unitId), overrides: {} };
  let enemy = { unitId: Number(defaultEnemy?.id), overrides: emptyEnemyOverrides(), fieldOverrides: {} };
  try {
    const saved = JSON.parse(localStorage.getItem(battleStorageKey));
    if (validDraft(saved)) {
      party = saved.party; cutin = saved.cutin; enemy = saved.enemy;
      partyProfiles = { ...(saved.partyProfiles || {}) };
      party.forEach((slot, index) => {
        const key = partyProfileKey(index, slot.unitId);
        if (!Object.hasOwn(partyProfiles, key)) partyProfiles[key] = { ...slot.overrides };
      });
    }
  } catch { /* Start from native defaults when no local draft is available. */ }
  let savedView = {};
  try { savedView = JSON.parse(localStorage.getItem(battleViewStorageKey)) || {}; } catch { /* Start at the first page when view state is unavailable. */ }
  let editMode = ["party", "cutin", "enemy"].includes(savedView.editMode) ? savedView.editMode : "party";
  let activeSlot = Number.isInteger(savedView.activeSlot) && savedView.activeSlot >= 0 && savedView.activeSlot < 5 ? savedView.activeSlot : 0;
  let resultsPages = savedView.resultsPages && typeof savedView.resultsPages === "object" && !Array.isArray(savedView.resultsPages) ? savedView.resultsPages : {};
  let resultsPage = Number.isInteger(resultsPages[editMode === "party" ? `party-${activeSlot}` : editMode]) && resultsPages[editMode === "party" ? `party-${activeSlot}` : editMode] > 0
    ? resultsPages[editMode === "party" ? `party-${activeSlot}` : editMode] : 1;
  const resultsPageSize = 32;
  let query = typeof savedView.query === "string" ? savedView.query : "";
  let drag = null;
  let saveTimer;
  const targetPageKey = () => editMode === "party" ? `party-${activeSlot}` : editMode;
  const pageForTarget = () => {
    const page = resultsPages[targetPageKey()];
    return Number.isInteger(page) && page > 0 ? page : 1;
  };
  const storeBattleView = () => {
    try {
      resultsPages[targetPageKey()] = resultsPage;
      localStorage.setItem(battleViewStorageKey, JSON.stringify({ editMode, activeSlot, query: document.querySelector("#battle-search").value, resultsPages }));
    } catch { /* Keep the editor usable when browser storage is unavailable. */ }
  };
  window.DG_LOCAL_DATA = scene;
  window.DG_BATTLE_IMAGE_IDS = Object.fromEntries(nativeUnits.map((unit) => [unit.id, unit.drawId]));
  shell("戰鬥配置工具", "照原生戰鬥場景編排隊伍、NS 橫幅與敵方立繪，並沿用原 APK 調整檔格式匯出。", `<div class="battle-tool" id="battle-tool">
    <div class="battle-toolbar"><span id="battle-save-status" role="status">調整只儲存在此瀏覽器</span><div><button class="button secondary" id="battle-import">匯入原生調整檔</button><button id="battle-export">匯出 APK 調整檔</button><input id="battle-file" type="file" accept=".json,application/json" hidden></div></div>
    <div class="battle-layout-grid"><section class="battle-preview-panel"><div class="battle-preview-head"><div><strong>原生戰鬥畫面預覽</strong><small>點選目標格，直接拖曳對應立繪調整位置</small></div><span>640 × 1136</span></div><div class="battle-canvas-wrap battle-native-stage"><canvas id="battle-canvas" width="640" height="1136" aria-label="可拖曳的原生戰鬥預覽"></canvas><canvas id="battle-overlay" width="640" height="1136" aria-hidden="true"></canvas></div><p class="battle-preview-note">戰鬥背景、敵區框、Normal Skill 橫幅、五格遮罩與圖集沿用原版；角色立繪取自 wiki 圖片。</p></section>
    <aside class="battle-controls"><section class="battle-control-card"><h2>選擇編輯區域</h2><label>目標<select id="battle-mode"><option value="enemy">敵人立繪</option><option value="cutin">NS 橫幅角色</option>${slotLabels.map((label, index) => `<option value="party-${index}">${index + 1}. ${esc(label)}</option>`).join("")}</select></label><label>搜尋角色<input id="battle-search" type="search" autocomplete="off" placeholder="輸入角色名稱或編號"></label><div class="battle-results" id="battle-results"></div><div class="battle-pagination" id="battle-pagination"></div></section>
    <section class="battle-control-card"><h2 id="battle-target-name">${esc(slotLabels[0])}</h2><p id="battle-current-unit" class="battle-current-unit"></p><label><span id="battle-size-title">格內取景縮放</span> <span id="battle-size-label"></span><input id="battle-size" type="range" min="40" max="1200" step="5"></label><button class="button secondary" id="battle-reset-slot">還原目前立繪原始位置</button><p class="battle-coordinate-hint" id="battle-edit-hint">隊伍外框固定；拖曳只調整遮罩框內的立繪取景。</p></section>
    <section class="battle-control-card"><h2>APK 匯出格式</h2><p>匯出沿用 DG Battle Lab 的 <code>dg-battle-presentation</code> schema 1、資料版本與 APK 指紋，包含隊伍槽、NS 立繪及敵人位置覆寫。</p><p class="muted">隊伍格對應 img_4（隊長／右側）至 img_0（好友／左側）；NS 與敵人各自輸出原生顯示欄位。</p></section></aside></div></div>`);
  const canvas = document.querySelector("#battle-canvas");
  const overlay = document.querySelector("#battle-overlay");
  const status = document.querySelector("#battle-save-status");
  const renderer = new window.DGBattleRenderer.Renderer(canvas, overlay, scene.layout, (error) => { status.textContent = `素材載入失敗：${error.message}`; });
  const resizeStage = () => renderer.resize(canvas.clientWidth, canvas.clientHeight);
  new ResizeObserver(resizeStage).observe(canvas);
  resizeStage();
  const modeSelect = document.querySelector("#battle-mode");
  document.querySelector("#battle-search").value = query;
  const currentId = () => editMode === "party" ? Number(party[activeSlot].unitId) : editMode === "cutin" ? Number(cutin.unitId) : Number(enemy.unitId);
  const fieldsForRole = (role, overrides = {}) => ({ ...availableById.get(Number(role.unitId)).fields, ...overrides });
  const fieldsForSlot = (slot) => fieldsForRole(slot, slot.overrides);
  const fieldsForCutin = () => fieldsForRole(cutin, cutin.overrides);
  const fieldsForEnemy = () => fieldsForRole(enemy, enemy.fieldOverrides);
  const enemyRecord = () => enemyById.get(Number(enemy.unitId)).enemies[0];
  const enemyPlacement = () => ({ ...enemyRecord(), ...enemy.overrides, pos_absolute: 2 });
  const updateVisual = (target, key, value) => {
    const number = Math.round(value), fixed = key.includes("tiling") ? Math.max(1, Math.min(4000, number)) : Math.max(-4000, Math.min(4000, number));
    target.overrides[key] = fixed;
  };
  const storeDraft = () => {
    party.forEach((slot, index) => { partyProfiles[partyProfileKey(index, slot.unitId)] = { ...slot.overrides }; });
    clearTimeout(saveTimer); status.textContent = "儲存中…";
    saveTimer = setTimeout(() => {
      try { localStorage.setItem(battleStorageKey, JSON.stringify({ format: "dg-wiki-native-battle-draft", dataVersion: scene.catalog.dataVersion, sourceApkSha256: scene.catalog.sourceApkSha256, party, partyProfiles, cutin, enemy })); if (status.isConnected) status.textContent = "已儲存在此瀏覽器"; }
      catch { if (status.isConnected) status.textContent = "無法儲存，請匯出 JSON"; }
    }, 120);
  };
  const renderTarget = () => {
    modeSelect.value = editMode === "party" ? `party-${activeSlot}` : editMode;
    const role = availableById.get(currentId()), wiki = wikiByDrawId.get(Number(role.drawId));
    const labels = { party: slotLabels[activeSlot], cutin: "NS 橫幅角色", enemy: "敵人立繪" };
    document.querySelector("#battle-target-name").textContent = labels[editMode];
    document.querySelector("#battle-current-unit").textContent = `${wiki?.name || role.name} · No.${id3(role.drawId)}`;
    const title = document.querySelector("#battle-size-title");
    const input = document.querySelector("#battle-size");
    if (editMode === "enemy") {
      const fields = fieldsForEnemy(); input.min = "16"; input.max = "1280"; input.step = "8"; input.value = fields.size_width;
      document.querySelector("#battle-size-label").textContent = `${fields.size_width} × ${fields.size_height}`; title.textContent = "敵人顯示尺寸";
      document.querySelector("#battle-edit-hint").textContent = "拖曳敵人網格調整原生座標；滾輪或滑桿調整敵人尺寸。";
    } else {
      const prefix = editMode === "party" ? `img_${4 - activeSlot}_` : "img_cutin_";
      const fields = editMode === "party" ? fieldsForSlot(party[activeSlot]) : fieldsForCutin();
      const tiling = fields[prefix + "tiling"]; input.min = "40"; input.max = "1200"; input.step = "5"; input.value = Math.max(40, Math.min(1200, tiling));
      document.querySelector("#battle-size-label").textContent = `${Math.round(tiling / 10)}%`;
      title.textContent = editMode === "party" ? "格內取景縮放" : "NS 橫幅取景縮放";
      document.querySelector("#battle-edit-hint").textContent = editMode === "party" ? "隊伍外框固定；拖曳只調整遮罩框內的立繪取景。" : "拖曳 NS 橫幅中的立繪調整取景；橫幅位置與 60:9 比例固定。";
    }
  };
  const renderResults = () => {
    const q = document.querySelector("#battle-search").value.trim().toLowerCase();
    const pool = editMode === "enemy" ? enemyChoices : available;
    const filtered = pool.filter((native) => {
      const wiki = wikiByDrawId.get(Number(native.drawId));
      return !q || `${wiki?.name || native.name} ${native.name} ${id3(native.drawId)} ${native.drawId}`.toLowerCase().includes(q);
    });
    const pageCount = Math.max(1, Math.ceil(filtered.length / resultsPageSize));
    resultsPage = Math.min(resultsPage, pageCount);
    storeBattleView();
    const results = filtered.slice((resultsPage - 1) * resultsPageSize, resultsPage * resultsPageSize);
    document.querySelector("#battle-results").innerHTML = results.map((native) => {
      const wiki = wikiByDrawId.get(Number(native.drawId));
      return `<button class="battle-result" type="button" data-unit-id="${esc(native.id)}"><img src="/img/icon/${id3(native.drawId)}-icon.png" alt="" loading="lazy" onerror="this.hidden=true"><span><strong>${esc(wiki?.name || native.name)}</strong><small>No.${esc(id3(native.drawId))}${editMode === "enemy" ? " · 有敵人資料" : ""}</small></span></button>`;
    }).join("") || `<p class="muted">找不到符合的角色。</p>`;
    const start = filtered.length ? (resultsPage - 1) * resultsPageSize + 1 : 0;
    const end = Math.min(resultsPage * resultsPageSize, filtered.length);
    document.querySelector("#battle-pagination").innerHTML = `<button type="button" class="button secondary" data-page="${resultsPage - 1}" ${resultsPage <= 1 ? "disabled" : ""}>上一頁</button><span>第 ${resultsPage} / ${pageCount} 頁 · ${start}–${end} / ${filtered.length} 名</span><button type="button" class="button secondary" data-page="${resultsPage + 1}" ${resultsPage >= pageCount ? "disabled" : ""}>下一頁</button>`;
  };
  const draw = () => {
    const partyUnits = party.map((slot) => availableById.get(Number(slot.unitId)));
    const partyFields = party.map(fieldsForSlot);
    const enemyUnit = enemyById.get(Number(enemy.unitId));
    renderer.scene({ unit: enemyUnit, fields: fieldsForEnemy(), enemyUnit, enemyFields: fieldsForEnemy(), cutinUnit: availableById.get(Number(cutin.unitId)), cutinFields: fieldsForCutin(), partyUnits, partyFields, mode: editMode, slot: activeSlot, showCutin: true, showEnemy: true, enemyCount: 1, enemy: enemyPlacement() });
    renderer.render();
  };
  const refresh = (save = true) => { renderTarget(); draw(); if (save) storeDraft(); };
  modeSelect.addEventListener("change", () => {
    const selected = modeSelect.value;
    if (selected.startsWith("party-")) { editMode = "party"; activeSlot = Number(selected.slice(6)); }
    else editMode = selected;
    resultsPage = pageForTarget();
    renderResults(); refresh(false);
  });
  document.querySelector("#battle-search").addEventListener("input", (event) => { query = event.target.value; resultsPage = 1; renderResults(); });
  document.querySelector("#battle-pagination").addEventListener("click", (event) => {
    const button = event.target.closest("[data-page]");
    if (!button || button.disabled) return;
    resultsPage = Number(button.dataset.page);
    renderResults();
  });
  document.querySelector("#battle-results").addEventListener("click", (event) => {
    const button = event.target.closest("[data-unit-id]"); if (!button) return;
    const id = Number(button.dataset.unitId);
    if (editMode === "party") {
      const previous = party[activeSlot];
      partyProfiles[partyProfileKey(activeSlot, previous.unitId)] = { ...previous.overrides };
      party[activeSlot] = { unitId: id, overrides: { ...(partyProfiles[partyProfileKey(activeSlot, id)] || {}) } };
    }
    else if (editMode === "cutin") cutin = { unitId: id, overrides: {} };
    else enemy = { unitId: id, overrides: emptyEnemyOverrides(), fieldOverrides: {} };
    refresh();
  });
  document.querySelector("#battle-size").addEventListener("input", (event) => {
    const value = Number(event.target.value);
    if (editMode === "enemy") {
      const fields = fieldsForEnemy(), ratio = fields.size_height / Math.max(1, fields.size_width);
      enemy.fieldOverrides.size_width = Math.max(16, Math.min(1280, Math.round(value)));
      enemy.fieldOverrides.size_height = Math.max(16, Math.min(1280, Math.round(value * ratio)));
    } else {
      const prefix = editMode === "party" ? `img_${4 - activeSlot}_` : "img_cutin_";
      updateVisual(editMode === "party" ? party[activeSlot] : cutin, prefix + "tiling", value);
    }
    refresh();
  });
  canvas.addEventListener("pointerdown", (event) => {
    const point = renderer.point(event), hit = renderer.hit(point);
    if (!hit || !["party", "cutin", "enemy"].includes(hit.kind)) return;
    editMode = hit.kind;
    if (hit.kind === "party") activeSlot = hit.slot;
    modeSelect.value = hit.kind === "party" ? `party-${activeSlot}` : editMode;
    resultsPage = pageForTarget();
    renderResults(); renderTarget();
    drag = { point, hit, fields: hit.kind === "party" ? fieldsForSlot(party[hit.slot]) : hit.kind === "cutin" ? fieldsForCutin() : null, enemy: hit.kind === "enemy" ? enemyPlacement() : null };
    canvas.setPointerCapture(event.pointerId); draw();
  });
  canvas.addEventListener("pointermove", (event) => {
    if (!drag) return;
    const point = renderer.point(event), dx = point[0] - drag.point[0], dy = point[1] - drag.point[1];
    if (drag.hit.kind === "enemy") {
      enemy.overrides.pos_absolute = 2;
      enemy.overrides.posx_offset = Math.max(-2000, Math.min(2000, Math.round((drag.enemy.pos_absolute === 2 ? drag.enemy.posx_offset : 0) + dx)));
      enemy.overrides.posy_offset = Math.max(-2000, Math.min(2000, Math.round((drag.enemy.pos_absolute === 2 ? drag.enemy.posy_offset : 0) - dy)));
    } else {
      const prefix = drag.hit.kind === "party" ? `img_${4 - drag.hit.slot}_` : "img_cutin_";
      const scale = drag.fields[prefix + "tiling"] / 1000, sy = drag.hit.kind === "cutin" ? 0.15 : 1, j = drag.hit.jacobian;
      const target = drag.hit.kind === "party" ? party[drag.hit.slot] : cutin;
      updateVisual(target, prefix + "offsetX", drag.fields[prefix + "offsetX"] - 1000 * scale * (j[0][0] * dx + j[0][1] * dy));
      updateVisual(target, prefix + "offsetY", drag.fields[prefix + "offsetY"] - 1000 * scale * sy * (j[1][0] * dx + j[1][1] * dy));
    }
    renderTarget(); draw(); storeDraft();
  });
  const endDrag = () => { drag = null; };
  canvas.addEventListener("pointerup", endDrag); canvas.addEventListener("pointercancel", endDrag);
  canvas.addEventListener("wheel", (event) => {
    event.preventDefault(); const direction = event.deltaY < 0 ? 1 : -1;
    if (editMode === "enemy") {
      const fields = fieldsForEnemy(), width = Math.max(16, Math.min(1280, fields.size_width + direction * 8)), ratio = fields.size_height / Math.max(1, fields.size_width);
      enemy.fieldOverrides.size_width = width; enemy.fieldOverrides.size_height = Math.max(16, Math.min(1280, Math.round(width * ratio)));
    } else {
      const prefix = editMode === "party" ? `img_${4 - activeSlot}_` : "img_cutin_", target = editMode === "party" ? party[activeSlot] : cutin;
      const fields = editMode === "party" ? fieldsForSlot(target) : fieldsForCutin(); updateVisual(target, prefix + "tiling", Math.max(40, Math.min(1200, fields[prefix + "tiling"] + direction * 10)));
    }
    refresh();
  }, { passive: false });
  document.querySelector("#battle-reset-slot").addEventListener("click", () => {
    if (editMode === "party") party[activeSlot].overrides = {};
    else if (editMode === "cutin") cutin.overrides = {};
    else enemy = { ...enemy, overrides: emptyEnemyOverrides(), fieldOverrides: {} };
    refresh();
  });
  document.querySelector("#battle-export").addEventListener("click", () => {
    const profiles = new Map();
    const ensureProfile = (id) => {
      if (!profiles.has(id)) profiles.set(id, { fields: { ...availableById.get(Number(id)).fields }, enemy_overrides: {} });
      return profiles.get(id);
    };
    party.forEach((slot, index) => Object.assign(ensureProfile(Number(slot.unitId)).fields, slot.overrides));
    Object.assign(ensureProfile(Number(cutin.unitId)).fields, cutin.overrides);
    const selectedEnemy = enemyById.get(Number(enemy.unitId)), enemyEntry = selectedEnemy.enemies[0];
    Object.assign(ensureProfile(Number(enemy.unitId)).fields, enemy.fieldOverrides);
    ensureProfile(Number(enemy.unitId)).enemy_overrides[enemyEntry.fix_id] = enemy.overrides;
    const characters = [...profiles].map(([id, profile]) => ({ id, fields: profile.fields, enemy_overrides: profile.enemy_overrides, completed: false, note: "", party_slot_rects: Array.from({ length: 5 }, (_, slot) => { const n = 4 - slot, f = profile.fields; return [f[`img_${n}_offsetX`] / 1000, f[`img_${n}_offsetY`] / 1000, f[`img_${n}_tiling`] / 1000, f[`img_${n}_tiling`] / 1000]; }) }));
    const output = { format: battleFormat, schema: 1, visual_revision: 2, data_version: scene.catalog.dataVersion, source_apk_sha256: scene.catalog.sourceApkSha256, created_at: new Date().toISOString(), description: "Native visual fields only. img_4=leader/right, img_0=friend/left. Party slot0..4 uses img4..0. NS cutin and enemy placement use native visual fields.", characters, wiki_party_slots: party.map((slot, index) => ({ slot: index, id: Number(slot.unitId), draw_id: Number(availableById.get(Number(slot.unitId)).drawId) })), wiki_nspecial: { id: Number(cutin.unitId), draw_id: Number(availableById.get(Number(cutin.unitId)).drawId) }, wiki_enemy: { id: Number(enemy.unitId), draw_id: Number(selectedEnemy.drawId), fix_id: Number(enemyEntry.fix_id) } };
    const blob = new Blob([JSON.stringify(output, null, 2)], { type: "application/json" });
    const link = document.createElement("a"); link.href = URL.createObjectURL(blob); link.download = `dg291-presentation-${new Date().toLocaleDateString("sv-SE")}.json`; link.click(); setTimeout(() => URL.revokeObjectURL(link.href), 1500);
  });
  document.querySelector("#battle-import").addEventListener("click", () => document.querySelector("#battle-file").click());
  document.querySelector("#battle-file").addEventListener("change", async (event) => {
    const file = event.target.files[0]; if (!file) return;
    try {
      if (file.size > 8 * 1024 * 1024) throw Error("JSON 檔案超過 8 MiB。");
      const value = JSON.parse(await file.text());
      if (value?.format !== battleFormat || value.schema !== 1 || value.data_version !== scene.catalog.dataVersion || value.source_apk_sha256 !== scene.catalog.sourceApkSha256 || !Array.isArray(value.characters)) throw Error("調整檔版本或來源 APK 不符合。");
      const imported = new Map();
      for (const character of value.characters) {
        const id = Number(character.id), fields = character.fields, overrides = character.enemy_overrides || {};
        if (!availableById.has(id) || imported.has(id) || !fields || nativeFields.some((key) => !Number.isInteger(fields[key]))) throw Error("角色或原生欄位資料不合法。");
        const owner = availableById.get(id), validEnemyIds = new Set(owner.enemies.map((entry) => String(entry.fix_id)));
        if (Object.entries(overrides).some(([fixId, values]) => !validEnemyIds.has(String(fixId)) || !validEnemyOverrides(values))) throw Error("敵人覆寫資料不合法。");
        imported.set(id, { fields, enemyOverrides: overrides });
      }
      const importedSlots = Array.isArray(value.wiki_party_slots) ? value.wiki_party_slots : [];
      party = party.map((slot, index) => {
        const row = importedSlots.find((entry) => Number(entry.slot) === index);
        const unitId = row && availableById.has(Number(row.id)) ? Number(row.id) : Number(slot.unitId);
        const fields = imported.get(unitId)?.fields, prefix = `img_${4 - index}_`;
        return { unitId, overrides: fields ? Object.fromEntries(["tiling", "offsetX", "offsetY"].map((name) => [`${prefix}${name}`, fields[`${prefix}${name}`]])) : {} };
      });
      const nsId = Number(value.wiki_nspecial?.id ?? cutin.unitId);
      if (!availableById.has(nsId)) throw Error("NS 角色編號不存在。");
      const nsFields = imported.get(nsId)?.fields;
      cutin = { unitId: nsId, overrides: nsFields ? Object.fromEntries(["tiling", "offsetX", "offsetY"].map((name) => [`img_cutin_${name}`, nsFields[`img_cutin_${name}`]])) : {} };
      const enemyId = Number(value.wiki_enemy?.id ?? enemy.unitId), nextEnemyProfile = enemyById.get(enemyId);
      if (!nextEnemyProfile) throw Error("敵人角色沒有對應的原生敵方資料。");
      const enemyFixId = String(value.wiki_enemy?.fix_id ?? nextEnemyProfile.enemies[0].fix_id);
      const enemyValues = imported.get(enemyId)?.enemyOverrides[enemyFixId] || emptyEnemyOverrides(), enemyFields = imported.get(enemyId)?.fields;
      enemy = { unitId: enemyId, overrides: enemyValues, fieldOverrides: enemyFields ? Object.fromEntries(["size_width", "size_height", "pivot", "side_offset"].map((key) => [key, enemyFields[key]])) : {} };
      refresh(); status.textContent = "已匯入原生調整檔";
    } catch (error) { status.textContent = `匯入失敗：${error.message}`; }
    finally { event.target.value = ""; }
  });
  renderResults(); refresh(false);
  const frame = () => { if (!canvas.isConnected) return; renderer.render(); requestAnimationFrame(frame); };
  frame();
}
function renderUnitList() {
  shell("角色列表", `資料快照收錄 ${db.units.length.toLocaleString()} 名角色。輸入名稱或編號篩選。`, `<div class="toolbar"><input id="unit-filter" placeholder="搜尋角色名稱 / 編號"><select id="element-filter"><option value="">全部屬性</option>${Object.entries(elementNames).filter(([key]) => key !== "0").map(([key, name]) => `<option value="${key}">${name}</option>`).join("")}</select><select id="race-filter"><option value="">全部種族</option>${Object.entries(raceNames).filter(([key]) => key !== "0").map(([key, name]) => `<option value="${key}">${name}</option>`).join("")}</select></div><div class="result-count" id="unit-count"></div><div id="unit-results"></div>`);
  const input = document.querySelector("#unit-filter");
  const element = document.querySelector("#element-filter");
  const race = document.querySelector("#race-filter");
  const results = document.querySelector("#unit-results");
  input.value = new URLSearchParams(location.search).get("q") || "";
  let page = 1;
  let filtered = [];
  const showPage = () => {
    const pageCount = Math.max(1, Math.ceil(filtered.length / unitPageSize));
    page = Math.min(page, pageCount);
    const start = (page - 1) * unitPageSize;
    document.querySelector("#unit-count").textContent = `顯示第 ${filtered.length ? start + 1 : 0}–${Math.min(start + unitPageSize, filtered.length)} 位，共 ${filtered.length.toLocaleString()} / ${db.units.length.toLocaleString()} 位角色`;
    results.innerHTML = `${unitCards(filtered.slice(start, start + unitPageSize))}<div class="pagination"><button class="button secondary" id="unit-prev" ${page <= 1 ? "disabled" : ""}>上一頁</button><span>第 ${page} / ${pageCount} 頁</span><button class="button secondary" id="unit-next" ${page >= pageCount ? "disabled" : ""}>下一頁</button></div>`;
    document.querySelector("#unit-prev").addEventListener("click", () => { page--; showPage(); });
    document.querySelector("#unit-next").addEventListener("click", () => { page++; showPage(); });
  };
  const update = () => {
    const query = input.value.trim().toLowerCase();
    filtered = db.units.filter((unit) => (!query || String(unit.draw_id).includes(query) || String(id3(unit.draw_id)).includes(query) || String(unit.name || "").toLowerCase().includes(query)) && (!element.value || String(unit.element) === element.value) && (!race.value || String(unit.kind) === race.value));
    page = 1;
    showPage();
  };
  input.addEventListener("input", update); element.addEventListener("change", update); race.addEventListener("change", update); update();
}

function renderUnit() {
  const { unit, translatedName: translated, skills, sameActiveUnits = [], linkData, relatedAreas } = db;
  if (!unit) return notFound("找不到這名角色");
  const references = [["隊長技能", skills.leader, "l"], ["主動技能", skills.active, "a"], ["普通技能 1", skills.normal1, "n"], ["普通技能 2", skills.normal2, "n"], ["被動技能", skills.passive, "p"]];
  const cardElements = { 1: ["none", "\u7121"], 2: ["fire", "\u706b"], 3: ["water", "\u6c34"], 4: ["light", "\u5149"], 5: ["dark", "\u95c7"], 6: ["wind", "\u98a8"], 7: ["life", "\u5fc3"] };
  const skillCards = references.map(([label, skill, type]) => {
    if (!skill) return "";
    const colorClass = ({ l: "leader", a: "active", n: "normal", p: "passive", ln: "link-active", lp: "link-passive" })[type];
    const costs = type === "n" ? [1, 2, 3, 4, 5].map((slot) => Number(skill[`cost${slot}`]) || 0).map((cost) => cardElements[cost] ? `<img src="/img/icon/${cardElements[cost][0]}.jpg" alt="${cardElements[cost][1]}\u9762\u677f" title="${cardElements[cost][1]}\u9762\u677f">` : "<span class=\"skill-cost-empty\" aria-hidden=\"true\"></span>").join("") : "";
    const turns = type === "a" ? `<span class="skill-meta">\u51b7\u537b\uff1a${esc(skill.use_turn)} \u56de\u5408\uff08\u6eff\u7b49\u5f8c ${esc(Math.max(0, Number(skill.use_turn) - Number(skill.level_max)))} \u56de\u5408\uff09</span>` : "";
    const sameOwners = type === "a" && sameActiveUnits.length ? `<div class="same-skill-owners"><span>\u6301\u6709\u76f8\u540c\u4e3b\u52d5\u6280\u80fd\u7684\u89d2\u8272</span><div>${sameActiveUnits.map((owner) => `<a class="skill-owner-link" href="${unitHref(owner)}" title="No.${esc(id3(owner.draw_id))} \u00b7 ${esc(owner.name)}">${unitImage(owner, "skill-owner-icon")}</a>`).join("")}</div></div>` : "";
    return `<article class="skill-block skill-block--${colorClass}"><header class="skill-block-head"><div><span class="skill-kind">${esc(label)}</span><strong>${esc(skill.name || "\u672a\u547d\u540d\u6280\u80fd")}</strong></div>${costs ? `<div class="skill-costs" aria-label="\u666e\u901a\u6280\u80fd\u9762\u677f\u9700\u6c42">${costs}</div>` : turns}</header><div class="skill-block-body"><p>${formatGameText(skill.detail || "")}</p>${skill.detailcn ? `<p class="skill-translation">${esc(skill.detailcn)}</p>` : ""}${sameOwners}<small><a href="/skill/${type}">\u6aa2\u8996${esc(label)}\u4e00\u89bd</a> \u00b7 ID ${esc(skill.fix_id)}</small></div></article>`;
  }).filter(Boolean).join("");
  const linkSkillDetails = Number(unit.link_enable) === 2 ? `<div class="link-skill-list">${skills.link ? `<article class="link-skill-row link-skill-row--active"><strong>Link Skill \u00b7 ${esc(skills.link.name)}</strong><p>${formatGameText(skills.link.detail || "")}</p>${skills.link.detailcn ? `<p class="skill-translation">${esc(skills.link.detailcn)}</p>` : ""}<small>\u767c\u52d5\u6a5f\u7387: ${(Number(skills.link.skill_link_odds) || 0) / 100}% ~ ${Math.min(100, (Number(skills.link.skill_link_odds) || 0) * 2 / 100)}%</small></article>` : ""}${skills.linkPassive ? `<article class="link-skill-row link-skill-row--passive"><strong>Link Passive \u00b7 ${esc(skills.linkPassive.name)}</strong><p>${formatGameText(skills.linkPassive.detail || "")}</p>${skills.linkPassive.detailcn ? `<p class="skill-translation">${esc(skills.linkPassive.detailcn)}</p>` : ""}</article>` : ""}</div>` : "";
  shell(`${id3(unit.draw_id)} · ${unit.name}`, `${raceNames[unit.kind] || "角色"} · ${elementNames[unit.element] || "屬性未記錄"}`, `<div class="detail-head">${unitArtwork(unit)}<div><div class="eyebrow">UNIT ARCHIVE · ${esc(unit.draw_id)}</div><h1>${esc(unit.name)}</h1>${translated ? `<p>${esc(translated)}</p>` : ""}<div class="chips"><span class="chip">${esc(elementNames[unit.element] || "未知屬性")}</span><span class="chip">${esc(raceNames[unit.kind] || "未知種族")}</span><span class="chip">★ ${esc(Number(unit.rare) + 1)}</span><span class="chip">Cost ${esc(unit.party_cost)}</span></div></div></div><div class="section-grid">${section("基本能力", rowTable([["編號", id3(unit.draw_id)], ["稀有度", `★ ${Number(unit.rare) + 1}`], ["屬性", elementNames[unit.element]], ["種族", raceNames[unit.kind]], ["Cost", unit.party_cost], ["最大等級", unit.level_max], ["HP", `${unit.base_hp_min} – ${unit.base_hp_max}`], ["攻擊", `${unit.base_attack_min} – ${unit.base_attack_max}`], ["防禦", `${unit.base_defense_min} – ${unit.base_defense_max}`], ["Link", Number(unit.link_enable) === 2 ? "可 Link" : "不可 Link"], ["系列", unit.series]]))}${section("技能", skillCards ? `<div class="skill-grid">${skillCards}</div>` : `<p class="muted">快照沒有這名角色的技能資料。</p>`)}${section("Link \u8cc7\u8a0a", linkData ? `${linkSkillDetails}<div class="link-calculator" data-link-hp="${esc(linkData.base?.hp || 0)}" data-link-atk="${esc(linkData.base?.atk || 0)}" data-cost="${esc(unit.party_cost)}" data-rare="${esc(unit.rare)}"><label>Link \u7b49\u7d1a <input class="link-level" type="number" min="${esc(unit.level_min)}" max="${esc(unit.level_max)}" value="${esc(unit.level_max)}"></label><span>HP <strong class="link-hp">--</strong></span><span>\u653b\u64ca <strong class="link-atk">--</strong></span><small>\u8f38\u5165\u7b49\u7d1a\u8a66\u7b97\u52a0\u6210\uff08\u6700\u5927\u7b49\u7d1a ${esc(unit.level_max)}\uff09</small></div><h3>\u7a2e\u65cf Link \u52a0\u6210</h3><div class="link-bonus-list">${linkData.raceBonuses.map((bonus) => `<div><strong>${esc(raceNames[bonus.kind] || `Kind ${bonus.kind}`)}</strong><span>${Number(bonus.hp) ? `HP +${esc(bonus.hp)}` : ""}${Number(bonus.atk) ? ` ATK +${Number(bonus.atk) / 100}%` : ""}${Number(bonus.crt) ? ` CRT +${Number(bonus.crt) / 100}%` : ""}${Number(bonus.bst) ? ` BOOST +${Number(bonus.bst) / 100}%` : ""}</span></div>`).join("") || `<p class="muted">\u6c92\u6709\u7a2e\u65cf\u52a0\u6210\u8cc7\u6599</p>`}</div><div class="link-material-columns"><div><h3>\u9032\u884c Link</h3>${unitCards(linkData.parts)}<p>\u5fc5\u8981\u91d1\u5e63\uff1a${esc(linkData.money || 0)}</p></div><div><h3>\u89e3\u9664 Link</h3>${unitCards(linkData.unlinkParts)}<p>\u5fc5\u8981\u91d1\u5e63\uff1a${esc(linkData.unlinkMoney || 0)}</p></div></div>` : `<p class="muted">\u6b64\u89d2\u8272\u4e0d\u652f\u63f4 Link</p>`) }${section("收錄於區域", `<div class="list">${relatedAreas.map((area) => `<a class="list-item" href="${areaHref(area)}">${esc(area.area_name)}</a>`).join("") || `<p class="muted">此快照未列出相關掉落區域。</p>`}</div>`)}</div><p><a href="/unitlist">← 返回角色列表</a></p>`);
  const calculator = document.querySelector(".link-calculator");
  if (calculator) {
    const input = calculator.querySelector(".link-level");
    const update = () => {
      const level = Math.min(Number(input.max), Math.max(Number(input.min), Number(input.value) || Number(input.min)));
      input.value = String(level);
      const scale = (1 + Number(calculator.dataset.cost) * 0.2 + (Number(calculator.dataset.rare) + 1) ** 2) * (1 + level * 0.03);
      calculator.querySelector(".link-hp").textContent = String(Math.floor(scale * Number(calculator.dataset.linkHp) / 100));
      calculator.querySelector(".link-atk").textContent = String(Math.floor(scale * Number(calculator.dataset.linkAtk) / 100));
    };
    input.addEventListener("input", update); update();
  }
}

function renderQuestList() {
  const areasByCategory = new Map();
  const questsByArea = new Map();
  for (const quest of db.quests) {
    const id = String(quest.area_id);
    questsByArea.set(id, [...(questsByArea.get(id) || []), quest]);
  }
  for (const area of db.areas) {
    const id = String(area.area_cate_id);
    areasByCategory.set(id, [...(areasByCategory.get(id) || []), area]);
  }
  const typeNames = {
    1: "\u666e\u901a\u5730\u4e0b\u57ce", 2: "\u55ae/\u96d9\u5468\u5730\u4e0b\u57ce", 3: "\u7dca\u6025\u5730\u4e0b\u57ce",
    4: "\u964d\u81e8\u5730\u4e0b\u57ce", 5: "\u6d3b\u52d5\u5730\u4e0b\u57ce", 6: "\u9032\u5316\u795e\u6bbf",
    7: "\u7121\u9650\u8ff4\u5eca", 8: "\u9280\u5e63\u5730\u4e0b\u57ce", 9: "\u5408\u4f5c\u5730\u4e0b\u57ce", 10: "\u9470\u5319\u5730\u4e0b\u57ce",
  };
  const columns = [[1, 6, 7, 3, 5, 10], [4, 2], [9], [8]];
  const typeCount = (type) => db.areaCategories.filter((category) => Number(category.area_cate_type) === type)
    .reduce((sum, category) => sum + (areasByCategory.get(String(category.fix_id)) || []).length, 0);
  const markup = columns.map((types) => `<div class="quest-type-column">${types.map((type) => {
    const categories = db.areaCategories.filter((category) => Number(category.area_cate_type) === type)
      .sort((a, b) => Number(a.questlist_sort) - Number(b.questlist_sort));
    const sections = categories.map((category) => {
      const areas = areasByCategory.get(String(category.fix_id)) || [];
      const areaLinks = areas.map((area) => {
        const quests = questsByArea.get(String(area.fix_id)) || [];
        const search = `${category.area_cate_name} ${area.area_name} ${area.area_name_eng} ${quests.map((quest) => quest.quest_name).join(" ")}`.toLowerCase();
        return `<a class="quest-area-link searchable" data-search="${esc(search)}" href="${areaHref(area)}"><strong>${esc(area.area_name)}</strong><small>${quests.length} \u500b\u95dc\u5361</small></a>`;
      }).join("");
      return `<details class="quest-category" data-category-search="${esc(category.area_cate_name.toLowerCase())}"><summary>${esc(category.area_cate_name)}<span>${areas.length}</span></summary><div class="quest-area-list">${areaLinks || `<p class="muted">\u6b64\u5206\u985e\u6c92\u6709\u5340\u57df\u8cc7\u6599</p>`}</div></details>`;
    }).join("");
    return `<section class="quest-type quest-type-${type}"><h2>${typeNames[type]}<small>${typeCount(type)} \u500b\u5340\u57df</small></h2><div>${sections}</div></section>`;
  }).join("")}</div>`).join("");
  shell("\u95dc\u5361\u8cc7\u6599", `\u539f\u7248\u4f9d 10 \u7a2e\u5730\u4e0b\u57ce\u985e\u578b\u5206\u7d44\u3002\u76ee\u524d\u5feb\u7167\u6536\u9304 ${db.areas.length.toLocaleString()} \u500b\u5340\u57df\u3001${db.quests.length.toLocaleString()} \u500b\u95dc\u5361\u3002`, `<div class="toolbar"><input id="quest-filter" placeholder="\u641c\u5c0b\u5206\u985e\u3001\u5340\u57df\u6216\u95dc\u5361\u540d\u7a31"></div><div class="quest-type-grid">${markup}</div>`);
  document.querySelector("#quest-filter").addEventListener("input", (event) => {
    const q = event.target.value.trim().toLowerCase();
    document.querySelectorAll(".quest-category").forEach((category) => {
      const matches = [...category.querySelectorAll(".searchable")];
      let visible = 0;
      matches.forEach((area) => { area.hidden = q !== "" && !area.dataset.search.includes(q); if (!area.hidden) visible++; });
      const categoryMatch = q !== "" && category.dataset.categorySearch.includes(q);
      if (categoryMatch) { matches.forEach((area) => area.hidden = false); visible = matches.length; }
      category.hidden = q !== "" && visible === 0;
      if (q !== "") category.open = visible > 0;
      else category.open = false;
    });
    document.querySelectorAll(".quest-type").forEach((type) => type.hidden = q !== "" && !type.querySelector(".quest-category:not([hidden])"));
  });
}
function renderArea() {
  const { area, category, quests } = db;
  if (!area) return notFound("找不到這個區域");
  const costs = [["火", area.cost1], ["水", area.cost2], ["風", area.cost3], ["光", area.cost4], ["闇", area.cost5], ["無", area.cost0], ["回復", area.cost6]].filter(([, v]) => Number(v) > 0).map(([n, v]) => `${n} ${Number(v) * 2}`).join(" · ");
  const map = area.res_map ? `<div class="area-map"><img src="/img/area/${esc(area.res_map)}_upper.png" alt="${esc(area.area_name)} 地圖上層" onerror="this.hidden=true"><img src="/img/area/${esc(area.res_map)}_lower.png" alt="${esc(area.area_name)} 地圖下層" onerror="this.hidden=true"></div>` : "";
  const areaIcons = `<div class="area-icons">${area.res_icon_key ? `<img src="/img/panel/${esc(area.res_icon_key)}.png" alt="區域圖示" onerror="this.hidden=true">` : ""}${area.res_icon_box ? `<img src="/img/panel/${esc(area.res_icon_box)}.png" alt="區域寶箱" onerror="this.hidden=true">` : ""}</div>`;
  shell(area.area_name, category?.area_cate_name || "區域資料", `${map}<div class="section-grid">${section("區域資訊", `${areaIcons}${rowTable([["英文名稱", area.area_name_eng], ["區域類型", area.type], ["體力需求", costs], ["資料說明", area.area_detail]])}`)}${section("區域關卡", `<div class="list">${quests.map((quest) => `<a class="list-item" href="${questHref(quest)}"><strong>${esc(quest.quest_name)}</strong><small>${esc(quest.difficulty_name)} · ${Number(quest.quest_stamina) ? `體力 ${esc(quest.quest_stamina)}` : "無體力資料"} · ${quest.floors || quest.floor_count || 0} 層</small></a>`).join("") || `<p class="muted">此區域沒有收錄關卡。</p>`}</div>`)}</div><p><a href="/questlist">← 返回關卡列表</a></p>`);
}

function actionEffect(action, atk) {
  const type = Number(action.skill_type);
  const p1 = Number(action.skill_param1) || 0;
  const p2 = Number(action.skill_param2) || 0;
  const elements = { 1: "\u7121", 2: "\u706b", 3: "\u6c34", 4: "\u5149", 5: "\u95c7", 6: "\u98a8", 7: "\u5fc3" };
  if (type === 1) return "\u53f0\u8a5e\u6216\u884c\u52d5\u63d0\u793a";
  if (type === 2) return `\u653b\u64ca\u529b ${p1}% \u50b7\u5bb3\uff08\u7d04 ${Math.floor(Number(atk) * p1 / 100)}\uff09`;
  if (type === 3) return `${elements[p1] || "\u5c6c\u6027\u672a\u77e5"}\u5c6c\u6027\u653b\u64ca ${p2}% \uff08\u7d04 ${Math.floor(Number(atk) * p2 / 100)}\uff09`;
  if (type === 5) return "\u9762\u677f\u6280\u80fd\u6548\u679c";
  if (type === 6) return `\u4f9d\u76ee\u6a19${p1 === 2 ? "\u6700\u5927" : "\u76ee\u524d"}\u751f\u547d\u503c\u9020\u6210 ${p2}% \u50b7\u5bb3`;
  if (type === 7) {
    const target = p1 === 1 ? "\u6575\u65b9" : p1 === 3 ? "\u6575\u65b9\u5168\u9ad4" : "\u6211\u65b9\u89d2\u8272";
    const amount = Number(action.skill_param4) || 0;
    return `${target} HP \u56de\u5fa9 ${amount}${Number(action.skill_param2) === 2 ? "%" : " HP"}`;
  }
  if (type === 8 || type === 9) return "\u89e3\u9664\u72c0\u614b\u6216\u7570\u5e38\u6548\u679c";
  if (type === 10) return "\u6280\u80fd\u683c\u6548\u679c\u8b8a\u66f4";
  if (type === 11) return "\u7279\u6b8a\u6280\u80fd\u6548\u679c";
  return "\u7279\u6b8a\u6548\u679c";
}
function renderAction(action, atk, count, selectionType) {
  const name = String(action.skill_name || "").trim();
  const effect = actionEffect(action, atk);
  const repeat = count > 1 ? selectionType === 1 ? `\u96a8\u6a5f\u9078\u64c7\u6b0a\u91cd \xd7${count}` : `\u5faa\u74b0\u5e8f\u5217\u4e2d\u91cd\u8907 ${count} \u6b21` : "";
  return `<div class="enemy-action"><b>${esc(name || effect)}</b>${name ? `<small>${esc(effect)}</small>` : ""}${repeat ? `<small>${repeat}</small>` : ""}</div>`;
}
function renderActionTable(table, atk) {
  const timing = Number(table.timing_type) === 2 ? `HP \u2264 ${esc(table.timing_param1)}%`
    : Number(table.timing_type) === 3 ? `\u7b2c ${esc(table.timing_param1)} \u56de\u5408\u5f8c` : "\u4e00\u822c\u689d\u4ef6";
  const selection = Number(table.action_select_type) === 1 ? "\u96a8\u6a5f\u9078\u64c7" : Number(table.action_select_type) === 2 ? "\u9806\u5e8f\u5faa\u74b0" : "\u884c\u52d5\u6e05\u55ae";
  const grouped = new Map();
  for (const action of table.actions) {
    const key = `${action.fix_id}:${(action.params || []).join(",")}`;
    const entry = grouped.get(key) || { action, count: 0 };
    entry.count++;
    grouped.set(key, entry);
  }
  return `<section class="enemy-action-table"><h4>${timing}<small>${selection}</small></h4>${[...grouped.values()].map(({ action, count }) => renderAction(action, atk, count, Number(table.action_select_type))).join("")}</section>`;
}
function enemyCard(enemy) {
  const unit = enemy.unit;
  const stats = [["HP", enemy.hp], ["ATK", enemy.atk], ["DEF", enemy.def], ["CD", enemy.cd], ["EXP", enemy.exp], ["\u91d1\u5e63", enemy.money]].filter(([, value]) => value != null && value !== "");
  const abilities = enemy.abilities?.length ? `<div class="enemy-abilities"><strong>\u6575\u4eba\u80fd\u529b</strong>${enemy.abilities.map((ability) => `<div><b>${esc(ability.name || "Ability")}</b><p>${esc(ability.detailcn || ability.detail || "")}</p></div>`).join("")}</div>` : "";
  const first = enemy.firstAction ? `<div class="enemy-special-action"><b>\u5148\u5236\u884c\u52d5\uff1a${esc(enemy.firstAction.skill_name || actionEffect(enemy.firstAction, enemy.atk))}</b>${enemy.firstAction.skill_name ? `<small>${esc(actionEffect(enemy.firstAction, enemy.atk))}</small>` : ""}</div>` : "";
  const death = enemy.deathAction ? `<div class="enemy-special-action"><b>\u6b7b\u4ea1\u884c\u52d5\uff1a${esc(enemy.deathAction.skill_name || actionEffect(enemy.deathAction, enemy.atk))}</b>${enemy.deathAction.skill_name ? `<small>${esc(actionEffect(enemy.deathAction, enemy.atk))}</small>` : ""}</div>` : "";
  const tables = (enemy.actionTables || []).map((table) => renderActionTable(table, enemy.atk)).join("");
  const summary = [enemy.firstAction ? "\u542b\u5148\u5236\u884c\u52d5" : "", (enemy.actionTables || []).length ? `${enemy.actionTables.length} \u7d44 HP/\u56de\u5408\u689d\u4ef6` : "", enemy.deathAction ? "\u542b\u6b7b\u4ea1\u884c\u52d5" : ""].filter(Boolean).join("\u3001");
  const actions = summary ? `<details class="enemy-actions"><summary>\u884c\u52d5\u660e\u7d30\uff08${summary}\uff09</summary>${first}${tables}${death}</details>` : "";
  return `<article class="enemy-card">${unit ? unitLink(unit) : `<strong>\u6575\u4eba #${esc(enemy.fix_id)}</strong>`}<div class="enemy-stats">${stats.map(([label, value]) => `<span><small>${label}</small><b>${esc(value)}</b></span>`).join("")}</div>${enemy.drop ? `<p class="enemy-drop">\u6389\u843d\uff1a${unitLink(enemy.drop)} <small>\u6a5f\u7387 ${esc(enemy.dropRate)}%</small></p>` : ""}${abilities}${actions}</article>`;
}
function enemyGroupBlock(group, title) {
  if (!group) return "";
  return `<section class="enemy-group"><h3>${title}${group.num_min != null ? ` \u00b7 \u6578\u91cf ${esc(group.num_min)}\u2013${esc(group.num_max)}` : ""}</h3><div class="enemy-grid">${group.enemies.map(enemyCard).join("") || `<p class="muted">\u6c92\u6709\u6575\u4eba\u8cc7\u6599</p>`}</div>${group.next ? `<p class="enemy-chain">\u5f8c\u7e8c\u6575\u7d44 \u00b7 \u56de\u5408\u9593\u9694 ${esc(group.chain_turn_offset || 0)}</p>${enemyGroupBlock(group.next, "")}` : ""}</section>`;
}
function renderQuest() {
  const { quest, area, requirement: req, floors, translatedStory: translated, bossUnit: boss } = db;
  if (!quest) return notFound("\u95dc\u5361\u8cc7\u6599\u4e0d\u5b58\u5728");
  const requirements = req ? Object.entries(req).filter(([key, value]) => /^(elem_|kind_)/.test(key) && Number(value) === 2).map(([key]) => key.replace("elem_", "\u5c6c\u6027 ").replace("kind_", "\u7a2e\u65cf ")) : [];
  const floorHtml = floors.length ? floors.map((floor, index) => `<article class="floor-detail"><h3>\u7b2c ${index + 1} \u5c64</h3><small>Floor ID ${esc(floor.fix_id)}</small>${floor.enemyGroups.map((entry) => enemyGroupBlock(entry.group, `\u6575\u4eba\u7de8\u6210 ${entry.slot} \u00b7 \u7d44\u5225 ${entry.id}`)).join("")}${floor.bossGroup ? enemyGroupBlock(floor.bossGroup, `Boss \u7de8\u6210 \u00b7 \u7d44\u5225 ${floor.bossGroup.fix_id}`) : ""}${!floor.enemyGroups.length && !floor.bossGroup ? `<p class="muted">\u6b64\u6a13\u5c64\u6c92\u6709\u53ef\u7528\u7684\u6575\u4eba\u7d44\u8cc7\u6599\u3002</p>` : ""}</article>`).join("") : `<p class="muted">\u6b64\u95dc\u5361\u6c92\u6709\u53ef\u7528\u6a13\u5c64\u660e\u7d30\u3002</p>`;
  shell(quest.quest_name, area?.area_name || "\u95dc\u5361\u8cc7\u6599", `<div class="section-grid">${section("\u95dc\u5361\u8cc7\u8a0a", rowTable([["\u96e3\u5ea6", quest.difficulty_name], ["\u9ad4\u529b", quest.quest_stamina], ["\u7968\u5238", quest.quest_ticket], ["\u9470\u5319", quest.quest_key], ["\u6a13\u5c64\u6578", floors.length || quest.floor_count], ["\u901a\u95dc\u7d93\u9a57", quest.clear_exp], ["\u901a\u95dc\u91d1\u5e63", quest.clear_money], ["\u901a\u95dc\u77f3\u982d", quest.clear_stone], ["\u9023\u6230", Number(quest.battle_chain) === 2 ? "\u662f" : "\u5426"], ["\u7e7c\u95dc", Number(quest.enable_continue) === 1 ? "\u5426" : "\u662f"], ["\u9650\u5236\u689d\u4ef6", quest.quest_requirement_text], ["\u5176\u4ed6\u9650\u5236", requirements.join("\u3001")]]))}${section("\u4e3b\u8981\u6575\u4eba", boss ? unitLink(boss) : `<p class="muted">\u8cc7\u6599\u5feb\u7167\u6c92\u6709\u6307\u5b9a\u4e3b\u8981\u6575\u4eba\u3002</p>`)}</div>${quest.story || translated ? section("\u6545\u4e8b\u6587\u672c", `<div class="story">${formatGameText(translated || quest.story || "", "quest")}</div>`) : ""}${section("\u6a13\u5c64\u8207\u6575\u4eba\u660e\u7d30", floorHtml)}<p><a href="${area ? areaHref(area) : "/questlist"}">\u2190 \u8fd4\u56de${area ? "\u5340\u57df" : "\u95dc\u5361\u5217\u8868"}</a></p>`);
}
function renderSkills(type) {
  const valid = ["n", "a", "l", "p", "ln", "lp"];
  if (!valid.includes(type)) type = "n";
  const filtered = (db.items || []).sort((a, b) => String(a.skill.name).localeCompare(String(b.skill.name), "ja"));
  shell(skillNames[type], "技能资料来自 2017-09-21 資料庫快照。", `<div class="skill-tabs">${["n", "a", "l", "p", "ln", "lp"].map((id) => `<a class="${id === type ? "active" : ""}" href="/skill/${id}">${skillNames[id]}</a>`).join("")}</div><div class="toolbar"><input id="skill-filter" placeholder="搜尋技能名稱或描述"><span class="muted">${filtered.length} 項</span></div><div class="list" id="skill-results">${filtered.map(({ skill, units }) => `<article class="list-item searchable" data-search="${esc(`${skill.name} ${skill.detail} ${skill.detailcn}`.toLowerCase())}"><strong>${esc(skill.name || "未命名技能")}</strong><p>${formatGameText(skill.detail || "")}</p>${skill.detailcn ? `<p>${esc(skill.detailcn)}</p>` : ""}<small>持有角色：${units.map((unit) => `<a href="${unitHref(unit)}">${esc(unit.name)}</a>`).join("、")}</small></article>`).join("") || `<p class="empty">此技能類別沒有可顯示的資料。</p>`}</div>`);
  document.querySelector("#skill-filter").addEventListener("input", (event) => { const q = event.target.value.toLowerCase(); document.querySelectorAll("#skill-results .searchable").forEach((item) => item.hidden = !item.dataset.search.includes(q)); });
}

function renderRanks() {
  const ranks = db.ranks.sort((a, b) => Number(a.fix_id) - Number(b.fix_id));
  shell("玩家等級", `快照收錄 ${ranks.length.toLocaleString()} 個等級資料。`, `<div class="panel"><table class="data-table"><thead><tr><th>等級</th><th>下一級經驗</th><th>累積經驗</th><th>體力</th><th>好友上限</th><th>角色上限</th><th>隊伍 Cost</th></tr></thead><tbody>${ranks.map((rank) => `<tr>${[rank.fix_id, rank.exp_next, rank.exp_next_total, rank.stamina, rank.friend_max, rank.unit_max, rank.party_cost].map((v) => `<td>${esc(v)}</td>`).join("")}</tr>`).join("")}</tbody></table></div>`);
}

function renderStory() {
  const entries = db.entries || [];
  const pageSize = 40;
  let page = 1;
  let activeEntry = null;
  shell("故事文本", `原 dgdb 故事頁提供角色翻譯查閱。快照收錄 ${entries.length.toLocaleString()} 筆角色文本。`, `<div class="toolbar"><input id="story-filter" placeholder="搜尋角色名稱、編號或文本"><span class="muted" id="story-count"></span></div><div class="story-browser"><div><div class="list" id="story-results"></div><div class="pagination" id="story-pagination"></div></div><section class="panel story-detail" id="story-detail"><p class="muted">請搜尋角色，或從左側清單選擇一位角色查看翻譯。</p></section></div>`);
  const renderDetail = () => {
    const panel = document.querySelector("#story-detail");
    if (!activeEntry) { panel.innerHTML = `<p class="muted">請搜尋角色，或從左側清單選擇一位角色查看翻譯。</p>`; return; }
    const { item, target } = activeEntry;
    panel.innerHTML = `<div class="story-detail-head"><div><div class="eyebrow">UNIT TRANSLATION · No.${esc(id3(target.draw_id))}</div><h2>${formatGameText(target.name)}</h2></div><a class="button secondary" href="${unitHref(target)}">查看角色資料</a></div><div class="story story-text">${formatGameText(item.text, "unit")}</div>`;
  };
  const renderChoices = () => {
    const query = document.querySelector("#story-filter").value.trim().toLowerCase();
    const filtered = entries.filter(({ item, target }) => `${target.name} ${target.draw_id} ${id3(target.draw_id)} ${plainGameText(item.text)}`.toLowerCase().includes(query));
    const pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
    page = Math.min(page, pageCount);
    const start = (page - 1) * pageSize;
    document.querySelector("#story-count").textContent = `${filtered.length.toLocaleString()} 筆角色文本`;
    document.querySelector("#story-results").innerHTML = filtered.slice(start, start + pageSize).map((entry) => `<button class="story-choice ${activeEntry?.item.id === entry.item.id ? "active" : ""}" type="button" data-story-id="${esc(entry.item.id)}"><span>${esc(id3(entry.target.draw_id))} · ${formatGameText(entry.target.name)}</span><small>${esc(plainGameText(entry.item.text).slice(0, 70))}${plainGameText(entry.item.text).length > 70 ? "…" : ""}</small></button>`).join("") || `<p class="empty">沒有符合條件的角色文本。</p>`;
    document.querySelector("#story-pagination").innerHTML = `<button class="button secondary" id="story-prev" ${page <= 1 ? "disabled" : ""}>上一頁</button><span>第 ${page} / ${pageCount} 頁</span><button class="button secondary" id="story-next" ${page >= pageCount ? "disabled" : ""}>下一頁</button>`;
    document.querySelector("#story-prev").addEventListener("click", () => { page--; renderChoices(); });
    document.querySelector("#story-next").addEventListener("click", () => { page++; renderChoices(); });
  };
  document.querySelector("#story-filter").addEventListener("input", () => { page = 1; renderChoices(); });
  document.querySelector("#story-results").addEventListener("click", (event) => {
    const button = event.target.closest("[data-story-id]");
    if (!button) return;
    activeEntry = entries.find(({ item }) => String(item.id) === button.dataset.storyId) || null;
    renderChoices();
    renderDetail();
  });
  renderChoices();
  renderDetail();
}

function renderVoteResults() {
  const results = db.voteResults || [];
  shell("人氣投票結果", "原 dgdb 網站留存的歷次人氣投票排名。", results.length ? `<div class="toolbar"><select id="vote-round">${results.map((round) => `<option value="${round.id}">第 ${round.id} 回</option>`).join("")}</select></div><div id="vote-results"></div>` : `<p class="empty">此資料庫快照沒有包含投票排名資料。</p>`);
  if (!results.length) return;
  const update = () => { const round = results.find((item) => String(item.id) === document.querySelector("#vote-round").value); document.querySelector("#vote-results").innerHTML = unitCards((round?.entries || []).map((entry) => ({ ...entry.unit, name: `${entry.rank}. ${entry.unit.name}` }))); };
  document.querySelector("#vote-round").addEventListener("change", update); update();
}

function notFound(message) { shell("找不到資料", "", `<p class="empty">${esc(message)}。資料可能不在 2017-09-21 快照內。</p><p><a href="/">回到首頁</a></p>`); }

async function loadJson(path) {
  if (!dataCache.has(path)) {
    const request = fetch(path).then((response) => {
      if (!response.ok) throw new Error(`資料檔無法載入：${path}`);
      return response.json();
    }).catch((error) => {
      dataCache.delete(path);
      throw error;
    });
    dataCache.set(path, request);
  }
  return dataCache.get(path);
}

function navigate(href, replace = false) {
  const url = new URL(href, location.href);
  if (url.origin !== location.origin) return;
  history[replace ? "replaceState" : "pushState"]({}, "", `${url.pathname}${url.search}${url.hash}`);
  route().catch(showRouteError);
  window.scrollTo(0, 0);
}

function showRouteError(error) {
  root.innerHTML = `<p class="empty">無法載入資料：${esc(error.message)}</p>`;
}

async function route() {
  const generation = ++routeGeneration;
  const parts = location.pathname.split("/").filter(Boolean).map(decodeURIComponent);
  const open = async (path, render, wrap = (value) => value) => {
    let value;
    try { value = await loadJson(path); }
    catch (error) { if (generation === routeGeneration) throw error; return; }
    if (generation !== routeGeneration) return;
    db = wrap(value);
    render();
  };
  if (!parts.length) return open("/data/home.json", renderHome);
  if (parts[0] === "battle") {
    try {
      const [units, scene] = await Promise.all([loadJson("/data/unit-index.json"), loadJson("/data/battle-scene.json")]);
      if (generation !== routeGeneration) return;
      db = { units, scene };
      renderBattleTool();
    } catch (error) { if (generation === routeGeneration) throw error; }
    return;
  }
  if (parts[0] === "unitlist") return open("/data/unit-index.json", renderUnitList, (units) => ({ units }));
  if (parts[0] === "unit") return open(`/data/unit/${encodeURIComponent(String(Number(parts[1])))}.json`, renderUnit);
  if (parts[0] === "questlist") return open("/data/quest-index.json", renderQuestList);
  if (parts[0] === "area") return open(`/data/area/${encodeURIComponent(parts[1])}.json`, renderArea);
  if (parts[0] === "quest") return open(`/data/quest/${encodeURIComponent(parts[1])}.json`, renderQuest);
  if (parts[0] === "skill") return open(`/data/skill/${encodeURIComponent(parts[1] || "n")}.json`, () => renderSkills(parts[1]), (items) => ({ items }));
  if (parts[0] === "rank") return open("/data/ranks.json", renderRanks, (ranks) => ({ ranks }));
  if (parts[0] === "story") return open("/data/story.json", renderStory, (entries) => ({ entries }));
  if (parts[0] === "voteresult") return open("/data/votes.json", renderVoteResults, (voteResults) => ({ voteResults }));
  notFound("這個頁面不存在");
}

document.querySelector("#menu-toggle").addEventListener("click", () => document.querySelector("#main-nav").classList.toggle("open"));
document.addEventListener("click", (event) => {
  const link = event.target.closest("a[href]");
  if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target || link.hasAttribute("download")) return;
  const url = new URL(link.href, location.href);
  if (url.origin !== location.origin) return;
  event.preventDefault();
  navigate(url.href);
  document.querySelector("#main-nav").classList.remove("open");
});
window.addEventListener("popstate", () => route().catch(showRouteError));
route().catch(showRouteError);
