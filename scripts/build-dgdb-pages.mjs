import { cp, mkdir, readFile, readdir, rm, writeFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const source = path.join(root, "archive-source");
const output = path.join(root, "dgdb-pages");
const dumpPath = path.join(source, "database", "dump_20170921.sql");
const wantedTables = new Set([
  "unit", "area", "area_category", "quest", "quest_floor", "quest_requirement",
  "n_skill", "a_skill", "p_skill", "l_skill", "link_system", "translate", "user_rank",
  "enemy_group", "enemy", "enemy_ability", "enemy_action_table", "enemy_action_param",
]);

function decodeSqlValue(raw) {
  const value = raw.trim();
  if (value.toUpperCase() === "NULL") return null;
  if (value.startsWith("'") && value.endsWith("'")) {
    return value.slice(1, -1).replace(/\\([0btnrZ\\'"%_])/g, (_, char) => ({
      "0": "\0", b: "\b", t: "\t", n: "\n", r: "\r", Z: "\x1a", "\\": "\\", "'": "'", '"': '"', "%": "%", "_": "_",
    })[char]);
  }
  if (/^-?\d+$/.test(value)) return Number(value);
  if (/^-?(?:\d+\.\d*|\d*\.\d+)(?:e[+-]?\d+)?$/i.test(value)) return Number(value);
  return value;
}

function parseValues(statement, start) {
  const rows = [];
  let i = start;
  while (i < statement.length) {
    while (i < statement.length && (statement[i] === "," || /\s/.test(statement[i]))) i++;
    if (statement[i] === ";" || i >= statement.length) break;
    if (statement[i] !== "(") { i++; continue; }
    i++;
    const row = [];
    let valueStart = i;
    let quoted = false;
    while (i < statement.length) {
      const char = statement[i];
      if (char === "\\" && quoted) { i += 2; continue; }
      if (char === "'") {
        if (quoted && statement[i + 1] === "'") { i += 2; continue; }
        quoted = !quoted;
      } else if (!quoted && (char === "," || char === ")")) {
        row.push(decodeSqlValue(statement.slice(valueStart, i)));
        if (char === ")") { i++; break; }
        valueStart = i + 1;
      }
      i++;
    }
    rows.push(row);
  }
  return rows;
}

function statementEnd(sql, start) {
  let quoted = false;
  for (let i = start; i < sql.length; i++) {
    if (sql[i] === "\\" && quoted) { i++; continue; }
    if (sql[i] === "'") {
      if (quoted && sql[i + 1] === "'") { i++; continue; }
      quoted = !quoted;
    } else if (!quoted && sql[i] === ";") return i + 1;
  }
  return sql.length;
}

async function parseSnapshot() {
  const sql = await readFile(dumpPath, "utf8");
  const tables = Object.create(null);
  let cursor = 0;
  while ((cursor = sql.indexOf("INSERT INTO `", cursor)) !== -1) {
    const end = statementEnd(sql, cursor);
    const statement = sql.slice(cursor, end);
    cursor = end;
    const match = statement.match(/^INSERT INTO `([^`]+)`\s*\(([^)]*)\)\s*VALUES\s*/);
    if (!match || !wantedTables.has(match[1])) continue;
    const table = match[1];
    const columns = match[2].split(",").map((column) => column.trim().replace(/^`|`$/g, ""));
    const rows = tables[table] ||= [];
    for (const values of parseValues(statement, match[0].length)) {
      if (values.length !== columns.length) throw new Error(`${table}: expected ${columns.length} values, got ${values.length}`);
      const row = Object.fromEntries(columns.map((column, index) => [column, values[index]]));
      delete row.created_at;
      delete row.updated_at;
      rows.push(row);
    }
  }
  return tables;
}

function compactRows(rows) {
  return (rows || []).filter((row) => Number(row.fix_id) !== 0);
}

await rm(output, { recursive: true, force: true });
await mkdir(path.join(output, "data"), { recursive: true });
await mkdir(path.join(output, "img", "icon"), { recursive: true });
await mkdir(path.join(output, "img", "large"), { recursive: true });
await mkdir(path.join(output, "img", "area"), { recursive: true });
await mkdir(path.join(output, "img", "panel"), { recursive: true });

const tables = await parseSnapshot();
const units = compactRows(tables.unit).filter((unit) => unit.draw_id != null).sort((a, b) => Number(a.draw_id) - Number(b.draw_id));
const areas = compactRows(tables.area).sort((a, b) => Number(a.questlist_sort) - Number(b.questlist_sort));
const quests = compactRows(tables.quest);
const skills = Object.fromEntries(["n_skill", "a_skill", "p_skill", "l_skill"].map((name) => [name, compactRows(tables[name])]));
const translations = compactRows(tables.translate);
const areaCategories = compactRows(tables.area_category);
const questFloors = compactRows(tables.quest_floor);
const questRequirements = compactRows(tables.quest_requirement);
const linkSystems = tables.link_system || [];
const enemies = compactRows(tables.enemy);
const enemyGroups = compactRows(tables.enemy_group);
const enemyAbilities = compactRows(tables.enemy_ability);
const enemyActionTables = compactRows(tables.enemy_action_table);
const enemyActionParams = compactRows(tables.enemy_action_param);
const ranks = compactRows(tables.user_rank);
const unitMap = new Map(units.map((unit) => [String(unit.draw_id), unit]));
const unitFixMap = new Map(units.map((unit) => [String(unit.fix_id), unit]));
const areaMap = new Map(areas.map((area) => [String(area.fix_id), area]));
const questMap = new Map(quests.map((quest) => [String(quest.fix_id), quest]));
const categoriesMap = new Map(areaCategories.map((category) => [String(category.fix_id), category]));
const skillMaps = Object.fromEntries(Object.entries(skills).map(([name, rows]) => [name, new Map(rows.map((row) => [String(row.fix_id), row]))]));
const requirementsMap = new Map(questRequirements.map((row) => [String(row.fix_id), row]));
const linkBaseMap = new Map(linkSystems.filter((row) => Number(row.kind) === 0).map((row) => [String(row.elem), row]));
const enemyMap = new Map(enemies.map((row) => [String(row.fix_id), row]));
const enemyGroupMap = new Map(enemyGroups.map((row) => [String(row.fix_id), row]));
const enemyAbilityMap = new Map(enemyAbilities.map((row) => [String(row.fix_id), row]));
const enemyActionTableMap = new Map(enemyActionTables.map((row) => [String(row.fix_id), row]));
const enemyActionParamMap = new Map(enemyActionParams.map((row) => [String(row.fix_id), row]));
const translationsBy = new Map();
for (const row of translations) translationsBy.set(`${row.type}:${row.id}`, row.text);
const floorsByQuest = new Map();
for (const floor of questFloors) floorsByQuest.set(String(floor.quest_id), [...(floorsByQuest.get(String(floor.quest_id)) || []), floor]);
const questsByArea = new Map();
for (const quest of quests) questsByArea.set(String(quest.area_id), [...(questsByArea.get(String(quest.area_id)) || []), quest]);
const relatedAreasByUnit = new Map();
for (const quest of quests) {
  if (!quest.clear_unit) continue;
  const area = areaMap.get(String(quest.area_id));
  const unit = unitMap.get(String(quest.clear_unit)) || unitFixMap.get(String(quest.clear_unit));
  if (area && unit) {
    const key = String(unit.draw_id);
    relatedAreasByUnit.set(key, [...(relatedAreasByUnit.get(key) || []), { fix_id: area.fix_id, area_name: area.area_name }]);
  }
}
const compactUnit = (unit) => unit ? Object.fromEntries(["fix_id", "draw_id", "name", "rare", "element", "kind", "sub_kind", "series"].map((key) => [key, unit[key]])) : null;
const nonzero = (value) => value != null && Number(value) !== 0;
const enemyAction = (id) => {
  const action = enemyActionParamMap.get(String(id));
  if (!action) return null;
  return { ...action, params: Array.from({ length: 16 }, (_, index) => action[`skill_param${index + 1}`]).filter(nonzero) };
};
const enrichEnemy = (enemy) => enemy ? {
  fix_id: enemy.fix_id, unit: compactUnit(unitFixMap.get(String(enemy.chara_id))),
  hp: enemy.status_hp, atk: enemy.status_pow, def: enemy.status_def, cd: enemy.status_turn,
  money: enemy.acquire_money, exp: enemy.acquire_exp,
  drop: compactUnit(unitFixMap.get(String(enemy.drop_unit_id))), dropRate: enemy.drop_unit_rate,
  abilities: [1, 2, 3, 4].map((i) => enemyAbilityMap.get(String(enemy[`ability${i}`]))).filter(Boolean),
  firstAction: enemyAction(enemy.act_first), deathAction: enemyAction(enemy.act_dead),
  actionTables: Array.from({ length: 8 }, (_, i) => enemyActionTableMap.get(String(enemy[`act_table${i + 1}`])))
    .filter(Boolean).map((table) => ({ ...table, actions: Array.from({ length: 8 }, (_, i) => enemyActionParamMap.get(String(table[`action_param_id${i + 1}`])))
      .filter(Boolean).map((action) => ({ ...action, params: Array.from({ length: 16 }, (_, i) => action[`skill_param${i + 1}`]).filter(nonzero) })) })),
} : null;
const enrichEnemyGroup = (id, followChain = false, seen = new Set()) => {
  if (seen.has(String(id))) return null;
  seen.add(String(id));
  const group = enemyGroupMap.get(String(id));
  if (!group) return null;
  const result = { fix_id: group.fix_id, num_min: group.num_min, num_max: group.num_max, chain_turn_offset: group.chain_turn_offset,
    enemies: Array.from({ length: 7 }, (_, i) => enemyMap.get(String(group[`enemy_id_${i + 1}`])))
      .filter(Boolean).map(enrichEnemy) };
  if (followChain && nonzero(group.chain_id)) result.next = enrichEnemyGroup(group.chain_id, true, seen);
  return result;
};
const enrichFloor = (floor) => ({ ...floor,
  enemyGroups: Array.from({ length: 7 }, (_, i) => ({ slot: i + 1, id: floor[`enemy_group_id_${i + 1}`] }))
    .filter((item) => nonzero(item.id)).map((item) => ({ ...item, group: enrichEnemyGroup(item.id) })),
  bossGroup: nonzero(floor.boss_group_id) ? enrichEnemyGroup(floor.boss_group_id, true) : null,
});
const unitsByActiveSkill = new Map();
for (const unit of units) {
  const skillId = String(unit.skill_limitbreak || 0);
  if (skillId === "0") continue;
  unitsByActiveSkill.set(skillId, [...(unitsByActiveSkill.get(skillId) || []), compactUnit(unit)]);
}
const writeJson = async (relative, value) => {
  const target = path.join(output, "data", relative);
  await mkdir(path.dirname(target), { recursive: true });
  await writeFile(target, JSON.stringify(value), "utf8");
};

const unitIndex = units.map(compactUnit);
const areaIndex = areas.map((area) => ({
  fix_id: area.fix_id, area_name: area.area_name, area_name_eng: area.area_name_eng,
  area_cate_id: area.area_cate_id, res_map: area.res_map,
  quest_count: (questsByArea.get(String(area.fix_id)) || []).length,
}));
const questIndex = quests.map((quest) => ({
  fix_id: quest.fix_id, area_id: quest.area_id, quest_name: quest.quest_name,
  difficulty_name: quest.difficulty_name, quest_stamina: quest.quest_stamina,
  quest_ticket: quest.quest_ticket, quest_key: quest.quest_key, floor_count: quest.floor_count,
}));
await writeJson("home.json", { snapshotDate: "2017-09-21", unitCount: units.length, areaCount: areas.length, questCount: quests.length });
await writeJson("unit-index.json", unitIndex);
await writeJson("quest-index.json", { areaCategories, areas: areaIndex, quests: questIndex });

const skillFields = {
  n: ["n_skill", ["skill_active0", "skill_active1"]], a: ["a_skill", ["skill_limitbreak"]],
  l: ["l_skill", ["skill_leader"]], p: ["p_skill", ["skill_passive"]],
  ln: ["n_skill", ["link_skill_active"]], lp: ["p_skill", ["link_skill_passive"]],
};
for (const unit of units) {
  const detailSkills = {
    leader: skillMaps.l_skill.get(String(unit.skill_leader)),
    active: skillMaps.a_skill.get(String(unit.skill_limitbreak)),
    normal1: skillMaps.n_skill.get(String(unit.skill_active0)),
    normal2: skillMaps.n_skill.get(String(unit.skill_active1)),
    passive: skillMaps.p_skill.get(String(unit.skill_passive)),
    link: skillMaps.n_skill.get(String(unit.link_skill_active)),
    linkPassive: skillMaps.p_skill.get(String(unit.link_skill_passive)),
  };
  const linkData = Number(unit.link_enable) === 2 ? {
    base: linkBaseMap.get(String(unit.element)) || null,
    raceBonuses: linkSystems.filter((row) => Number(row.elem) === 0 && Number(row.rare) === Number(unit.rare)
      && [Number(unit.kind), Number(unit.sub_kind)].includes(Number(row.kind)) && Number(row.kind) !== 0),
    parts: [1, 2, 3].map((i) => compactUnit(unitFixMap.get(String(unit[`link_unit_id_parts${i}`])))).filter(Boolean),
    money: unit.link_money,
    unlinkParts: [1, 2, 3].map((i) => compactUnit(unitFixMap.get(String(unit[`link_del_unit_id_parts${i}`])))).filter(Boolean),
    unlinkMoney: unit.link_del_money,
  } : null;
  const relatedAreas = relatedAreasByUnit.get(String(unit.draw_id)) || [];
  await writeJson(`unit/${unit.draw_id}.json`, { unit, translatedName: translationsBy.get(`2:${unit.draw_id}`) || "", skills: detailSkills, sameActiveUnits: unitsByActiveSkill.get(String(unit.skill_limitbreak || 0)) || [], linkData, relatedAreas });
}
for (const area of areas) {
  const areaQuests = questsByArea.get(String(area.fix_id)) || [];
  await writeJson(`area/${area.fix_id}.json`, {
    area, category: categoriesMap.get(String(area.area_cate_id)) || null,
    quests: areaQuests.map((quest) => ({ ...quest, floors: (floorsByQuest.get(String(quest.fix_id)) || []).length })),
  });
}
for (const quest of quests) {
  const area = areaMap.get(String(quest.area_id)) || null;
  const requirement = requirementsMap.get(String(quest.quest_requirement_id)) || null;
  await writeJson(`quest/${quest.fix_id}.json`, {
    quest, area, requirement, floors: (floorsByQuest.get(String(quest.fix_id)) || []).map(enrichFloor),
    translatedStory: translationsBy.get(`1:${quest.fix_id}`) || "",
    bossUnit: compactUnit(unitFixMap.get(String(quest.boss_chara_id))),
    clearUnit: compactUnit(unitFixMap.get(String(quest.clear_unit))),
  });
}
for (const [type, [table, fields]] of Object.entries(skillFields)) {
  const owners = new Map();
  for (const unit of units) for (const field of fields) {
    const id = String(unit[field] || 0);
    if (id !== "0") owners.set(id, [...(owners.get(id) || []), compactUnit(unit)]);
  }
  const items = skills[table].map((skill) => ({ skill, units: owners.get(String(skill.fix_id)) || [] })).filter((item) => item.units.length);
  await writeJson(`skill/${type}.json`, items);
}
await writeJson("ranks.json", ranks);
const storyEntries = translations.filter((item) => Number(item.type) === 2).map((item) => {
  const target = unitMap.get(String(item.id));
  return target ? { item, target: compactUnit(target) } : null;
}).filter(Boolean).sort((a, b) => Number(a.target.draw_id) - Number(b.target.draw_id));
await writeJson("story.json", storyEntries);
const cacheSource = await readFile(path.join(source, "app", "Util", "CacheUtil.php"), "utf8");
const voteMethodStart = cacheSource.indexOf("public function voteResult($id)");
const voteMethodEnd = cacheSource.indexOf("\n    }", voteMethodStart);
const voteMethod = cacheSource.slice(voteMethodStart, voteMethodEnd);
const voteResults = [...voteMethod.matchAll(/case\s+(\d+):\s*\$data\s*=\s*array\(([\s\S]*?)\);/g)].map(([, id, block]) => ({
  id,
  entries: [...block.matchAll(/'rank'\s*=>\s*'(\d+)'[\s\S]*?where\('draw_id','=','(\d+)'\)/g)].map(([, rank, drawId]) => ({ rank, unit: compactUnit(unitMap.get(drawId)) })).filter((entry) => entry.unit),
})).filter((round) => round.entries.length);
await writeJson("votes.json", voteResults);

const publicRoot = path.join(source, "public");
for (const shared of ["favicon.ico", "favicon.png"]) {
  try { await cp(path.join(publicRoot, shared), path.join(output, shared)); } catch { /* optional legacy asset */ }
}
const iconRoot = path.join(publicRoot, "img", "icon");
const iconFiles = new Set(await readdir(iconRoot));
const wantedIcons = new Set(["fire.png", "water.png", "wind.png", "light.png", "dark.png", "none.png", "heal.png", "fire.jpg", "water.jpg", "wind.jpg", "light.jpg", "dark.jpg", "none.jpg", "life.jpg"]);
for (const unit of units) {
  const id = String(unit.draw_id).padStart(3, "0");
  for (const candidate of [`${id}-icon.png`, `${id}.png`]) if (iconFiles.has(candidate)) wantedIcons.add(candidate);
}
let copiedIcons = 0;
for (const name of wantedIcons) {
  if (!iconFiles.has(name)) continue;
  await cp(path.join(iconRoot, name), path.join(output, "img", "icon", name));
  copiedIcons++;
}

const largeImageRoot = path.join(publicRoot, "img", "large");
const largeImageFiles = new Set(await readdir(largeImageRoot));
let copiedLargeImages = 0;
for (const unit of units) {
  const name = `${String(unit.draw_id).padStart(3, "0")}.png`;
  if (largeImageFiles.has(name)) {
    await cp(path.join(largeImageRoot, name), path.join(output, "img", "large", name));
    copiedLargeImages++;
  }
}

let copiedAreaAssets = 0;
const areaAssetRoot = path.join(publicRoot, "img", "area");
const areaAssetFiles = new Set(await readdir(areaAssetRoot));
const panelRoot = path.join(publicRoot, "img", "panel");
const panelFiles = new Set(await readdir(panelRoot));
for (const area of areas) {
  if (area.res_map) {
    for (const suffix of ["_upper.png", "_lower.png"]) {
      const name = `${area.res_map}${suffix}`;
      if (areaAssetFiles.has(name)) {
        await cp(path.join(areaAssetRoot, name), path.join(output, "img", "area", name));
        copiedAreaAssets++;
      }
    }
  }
  for (const key of [area.res_icon_key, area.res_icon_box]) {
    if (!key) continue;
    const name = `${key}.png`;
    if (panelFiles.has(name)) {
      await cp(path.join(panelRoot, name), path.join(output, "img", "panel", name));
      copiedAreaAssets++;
    }
  }
}

for (const name of ["index.html", "app.js", "styles.css", "_redirects"]) {
  await cp(path.join(root, "dgdb-pages-src", name), path.join(output, name));
}
await cp(path.join(root, "dgdb-pages-src", "battle-renderer.js"), path.join(output, "battle-renderer.js"));
await cp(path.join(root, "dgdb-pages-src", "data", "battle-scene.json"), path.join(output, "data", "battle-scene.json"));
await cp(path.join(root, "dgdb-pages-src", "battle-atlas"), path.join(output, "battle-atlas"), { recursive: true });
console.log(`Built dgdb static archive: ${units.length} units, ${areas.length} areas, ${quests.length} quests, ${copiedIcons} local icons, ${copiedLargeImages} character artworks, ${copiedAreaAssets} area assets, ${voteResults.length} vote rounds.`);
