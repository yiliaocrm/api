<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>访问受限提示</title>
  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
      margin: 0;
      font-family: 'Segoe UI', 'Microsoft YaHei', Arial, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .container {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
      padding: 48px 32px 32px 32px;
      max-width: 400px;
      width: 100%;
      text-align: center;
      position: relative;
    }
    .icon {
      font-size: 48px;
      color: #ff9800;
      margin-bottom: 16px;
      display: block;
    }
    .title {
      font-size: 24px;
      font-weight: bold;
      color: #333;
      margin-bottom: 12px;
    }
    .desc {
      font-size: 16px;
      color: #666;
      margin-bottom: 24px;
      line-height: 1.6;
    }
    .divider {
      height: 1px;
      background: #eee;
      margin: 24px 0;
      border: none;
    }
    .footer {
      font-size: 13px;
      color: #aaa;
      margin-top: 16px;
    }
  </style>
</head>
<body>
  <div class="container">
    <span class="icon">&#9888;</span>
    <div class="title">访问受限</div>
    <div class="desc">
      <p><strong style="color: red;">{{ $message }}</strong></p>
    </div>
    <hr class="divider">
    <div class="footer">如需帮助，请联系系统管理员。</div>
  </div>
</body>
</html>
