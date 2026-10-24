# Divine Gate Wiki

這是獨立的 Divine Gate 靜態資料站專案，使用 2017-09-21 的 dgdb SQL 快照還原角色、技能、區域、關卡與故事資料。

## 本機預覽

安裝 Node.js 後，在此專案目錄執行：

```powershell
npm.cmd run preview
```

這會先重建網站，再以 Wrangler Pages 提供本機預覽，預設位址是 http://localhost:8788/。

## Cloudflare Pages 部屬

首次使用時，先登入 Cloudflare 並建立專案：

```powershell
npx.cmd wrangler login
npx.cmd wrangler pages project create dgdb-archive
```

之後執行 `npm.cmd run deploy` 即可重建並部屬。

## 專案內容

- `dgdb-pages-src/`：網站頁面與樣式原始碼。
- `scripts/build-dgdb-pages.mjs`：從本地 SQL 快照產生靜態 JSON 與網站輸出。
- `archive-source/`：建置所需的 SQL、圖片素材與投票資料來源。
- `dgdb-pages/`：建置後的 Cloudflare Pages 靜態輸出，不納入 Git。

資料是歷史快照，不代表遊戲目前狀態。
