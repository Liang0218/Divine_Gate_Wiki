# dgdb Cloudflare Pages 靜態典藏版

這個目錄只輸出原 dgdb 網站的唯讀典藏頁面，不會包含 repo 根目錄的戰鬥原型或 Android 測試頁。

## 建置

在 repo 根目錄執行：

```sh
npm.cmd run build:dgdb-pages
```

建置結果會寫入 `dgdb-pages/`。建置需要本機的 `dgdb/database/dump_20170921.sql` 和 `dgdb/public/img/icon/`。

## 部署到 Cloudflare Pages

1. 先在本機執行上面的建置指令。
2. 用 Wrangler 將 `dgdb-pages/` 作為靜態資產部署目錄上傳。這份輸出有超過 1,000 個檔案，因此使用 Wrangler，不使用 Dashboard 拖放上傳。

```sh
npx.cmd wrangler login
npx.cmd wrangler pages project create dgdb-archive
npx.cmd wrangler pages deploy dgdb-pages --project-name=dgdb-archive
```

首次建立專案時，Cloudflare 會要求選擇正式分支。後續只需重建並重跑 `pages deploy`。

資料存於 `data/`，並以多個小 JSON 檔分段，避免超過 Pages 單一檔案大小限制。頁面依 URL 顯示角色、關卡、技能、排名、故事和投票資料。網址路徑由 `_redirects` 交給靜態前端處理。
