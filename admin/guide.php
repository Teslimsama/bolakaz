<?php
include 'session.php';
app_admin_require_roles(['admin', 'staff']);
include 'header.php';
?>

<body class="hold-transition skin-blue sidebar-mini">
  <div class="wrapper">
    <?php include 'navbar.php'; ?>
    <?php include 'menubar.php'; ?>

    <div class="content-wrapper">
      <section class="content-header">
        <h1>How To Use App</h1>
        <ol class="breadcrumb">
          <li><a href="home"><i class="fa fa-home"></i> Home</a></li>
          <li class="active">How To Use App</li>
        </ol>
      </section>

      <section class="content guide-page">
        <div class="guide-hero box box-primary">
          <div class="box-body">
            <p class="guide-eyebrow">Simple staff guide</p>
            <h2>Bolakaz step-by-step guide</h2>
            <p class="guide-intro">
              This page explains the admin in plain English. If you forget what to do, open
              <strong>Help</strong> and click <strong>How To Use App</strong>.
            </p>
            <p class="guide-intro">
              The safest daily habit is simple: enter sales carefully, record payments immediately, and use
              <strong>Sync Now</strong> before you close for the day.
            </p>
          </div>
        </div>

        <div class="guide-jump box">
          <div class="box-body">
            <div class="guide-jump-grid">
              <a href="#sign-in">Sign In</a>
              <a href="#dashboard">Dashboard</a>
              <a href="#customers">Customers</a>
              <a href="#products">Products</a>
              <a href="#csv">Import CSV</a>
              <a href="#categories">Categories</a>
              <a href="#online-sales">Online Sales</a>
              <a href="#bank-transfer">Bank Transfer</a>
              <a href="#offline-sales">Offline Sales</a>
              <a href="#payments">Payments</a>
              <a href="#statements">Statements</a>
              <a href="#shipping">Shipping</a>
              <a href="#coupons">Coupons</a>
              <a href="#banners">Banners</a>
              <a href="#ads">Ads</a>
              <a href="#web-details">Web Details</a>
              <a href="#sync">Sync Basics</a>
              <a href="#mistakes">Common Mistakes</a>
              <a href="#troubleshooting">Troubleshooting</a>
              <a href="#end-of-day">End of Day</a>
            </div>
          </div>
        </div>

        <div class="guide-section box" id="sign-in">
          <div class="box-body">
            <h3>1. Sign In</h3>
            <ol>
              <li>Open the admin sign-in page.</li>
              <li>Type your email and password.</li>
              <li>Click <strong>Sign in</strong>.</li>
              <li>If you forget your password, tell the admin so they can use <strong>Reset Password</strong> from the <strong>Customers</strong> page.</li>
            </ol>
          </div>
        </div>

        <div class="guide-section box" id="dashboard">
          <div class="box-body">
            <h3>2. Dashboard</h3>
            <ol>
              <li>Click <strong>Dashboard</strong> on the left.</li>
              <li>Use <strong>Year</strong>, <strong>Start Date</strong>, and <strong>End Date</strong> to choose the period you want.</li>
              <li>Click <strong>Apply Filters</strong>.</li>
              <li>Read the cards at the top to see total revenue, offline money collected, outstanding balance, total orders, total products, and total users.</li>
              <li>Use the charts to quickly understand what is selling well.</li>
            </ol>
          </div>
        </div>

        <div class="guide-section box" id="customers">
          <div class="box-body">
            <h3>3. Customers</h3>
            <ol>
              <li>Click <strong>Customers</strong>.</li>
              <li>Click <strong>Add Customer</strong> when you want to create a new customer record.</li>
              <li>Use <strong>Edit</strong> to fix a customer name, phone number, or address.</li>
              <li>Use <strong>Enable Login</strong> if the customer has never logged in before.</li>
              <li>Use <strong>Reset Password</strong> if the customer already has access but cannot sign in.</li>
              <li>Use <strong>Cart</strong> if you want to see what the customer has already added to cart.</li>
            </ol>
            <p class="guide-note">
              Tip: before creating a new customer, search first so you do not create the same person twice.
            </p>
          </div>
        </div>

        <div class="guide-section box" id="products">
          <div class="box-body">
            <h3>4. Products</h3>
            <ol>
              <li>Open <strong>Catalog</strong>, then click <strong>Product List</strong>.</li>
              <li>Click <strong>Add Product</strong> to enter a new item.</li>
              <li>Fill the product name, price, quantity, category, and the product details carefully.</li>
              <li>Use <strong>Edit</strong> when something needs to change.</li>
              <li>Use <strong>Label</strong> for one product if you want to print a single label.</li>
              <li>Use <strong>Archive</strong> if you do not want the product to show as active anymore.</li>
            </ol>
            <p class="guide-note">
              The SKU is important. Use the printed label or camera scan during offline sales whenever possible.
            </p>
          </div>
        </div>

        <div class="guide-section box" id="csv">
          <div class="box-body">
            <h3>5. Import CSV And Print Labels</h3>
            <ol>
              <li>From <strong>Product List</strong>, click <strong>Import CSV</strong> if you are adding many products at once.</li>
              <li>After the import, open the list and quickly check that names, prices, quantity, colors, and sizes look correct.</li>
              <li>To print many labels, tick the products you want.</li>
              <li>Use <strong>Select Page</strong> to select all products on the current page.</li>
              <li>Use <strong>Clear Selection</strong> if you picked the wrong items.</li>
              <li>Click <strong>Print Labels</strong> when you are ready.</li>
            </ol>
          </div>
        </div>

        <div class="guide-section box" id="categories">
          <div class="box-body">
            <h3>6. Categories</h3>
            <ol>
              <li>Open <strong>Catalog</strong>, then click <strong>Categories</strong>.</li>
              <li>Click <strong>Add Category</strong> to create a new main category or child category.</li>
              <li>Use clear names that staff and customers will understand easily.</li>
              <li>Keep categories tidy. If two categories mean the same thing, use one standard name.</li>
            </ol>
          </div>
        </div>

        <div class="guide-section box" id="online-sales">
          <div class="box-body">
            <h3>7. Online Sales</h3>
            <ol>
              <li>Click <strong>Sales</strong>.</li>
              <li>Use the date filters if you want a specific period.</li>
              <li>Click <strong>View</strong> on any order to inspect what the customer bought.</li>
              <li>Check the order status, customer details, payment details, and delivery address before taking action.</li>
              <li>If you need a paper copy, click <strong>Print Report</strong> after choosing the correct date range.</li>
            </ol>
          </div>
        </div>

        <div class="guide-section box" id="bank-transfer">
          <div class="box-body">
            <h3>8. Bank Transfer Confirmation</h3>
            <ol>
              <li>Open <strong>Sales</strong>.</li>
              <li>Look for orders waiting for manual payment confirmation.</li>
              <li>Click <strong>Confirm Payment</strong>.</li>
              <li>Only confirm after you have truly seen the money in the bank or you have proper proof.</li>
              <li>After confirming, check the order again with <strong>View</strong> if you want to be sure the status changed.</li>
            </ol>
            <p class="guide-note">
              Never click <strong>Confirm Payment</strong> just because a customer says they paid. Confirm first.
            </p>
          </div>
        </div>

        <div class="guide-section box" id="offline-sales">
          <div class="box-body">
            <h3>9. Offline Sales</h3>
            <ol>
              <li>Click <strong>Offline Sales</strong>.</li>
              <li>Click <strong>New Offline Sale</strong>.</li>
              <li>Search for the customer or create the customer first if needed.</li>
              <li>Under <strong>Scan or Type</strong>, scan the product code or type the SKU.</li>
              <li>If you want to use the phone or laptop camera, click <strong>Scan with Camera</strong>.</li>
              <li>If the scan is not working, use <strong>Fallback Picker</strong> to choose the product manually.</li>
              <li>Check quantity, price, due date, and customer name before saving.</li>
              <li>Save the sale only when all items are correct.</li>
            </ol>
            <p class="guide-note">
              The fastest entry is to scan the printed SKU label. If that fails, you can type the full SKU like <strong>BLKZ-000123</strong> or only the number part like <strong>123</strong>.
            </p>
          </div>
        </div>

        <div class="guide-section box" id="payments">
          <div class="box-body">
            <h3>10. Record Offline Payments</h3>
            <ol>
              <li>Open <strong>Offline Sales</strong>.</li>
              <li>Find the correct sale.</li>
              <li>Click <strong>Payments</strong>.</li>
              <li>Enter the amount paid, payment method, payment date, and any useful note.</li>
              <li>Save immediately after the customer pays.</li>
            </ol>
            <p class="guide-note">
              Record every payment the same day. Do not wait until later, because small mistakes become hard to fix.
            </p>
          </div>
        </div>

        <div class="guide-section box" id="statements">
          <div class="box-body">
            <h3>11. Statement And WhatsApp</h3>
            <ol>
              <li>Open <strong>Offline Sales</strong>.</li>
              <li>Use <strong>Statement</strong> to open the customer statement page.</li>
              <li>Use <strong>WhatsApp</strong> if you want to send the customer a ready-made WhatsApp message with the statement link.</li>
              <li>Before sending, confirm the customer phone number is correct.</li>
              <li>If the balance changed after a new payment, refresh the statement and send the new one.</li>
            </ol>
          </div>
        </div>

        <div class="guide-section box" id="shipping">
          <div class="box-body">
            <h3>12. Shipping</h3>
            <ol>
              <li>Click <strong>Shipping</strong>.</li>
              <li>Click <strong>Add Shipping Method</strong> to add a new delivery option.</li>
              <li>Use clear names so staff know what the method means.</li>
              <li>Check the price carefully before saving.</li>
            </ol>
          </div>
        </div>

        <div class="guide-section box" id="coupons">
          <div class="box-body">
            <h3>13. Coupons</h3>
            <ol>
              <li>Click <strong>Coupons</strong>.</li>
              <li>Click <strong>Add Coupon</strong>.</li>
              <li>Choose the correct type and value.</li>
              <li>Set the correct expiry date before saving.</li>
              <li>Test the coupon once if it is an important promotion.</li>
            </ol>
          </div>
        </div>

        <div class="guide-section box" id="banners">
          <div class="box-body">
            <h3>14. Banners</h3>
            <ol>
              <li>Click <strong>Banners</strong>.</li>
              <li>Click <strong>Add Banner</strong>.</li>
              <li>Upload the image and write the heading and caption carefully.</li>
              <li>Make sure the link points to the right product or page.</li>
            </ol>
          </div>
        </div>

        <div class="guide-section box" id="ads">
          <div class="box-body">
            <h3>15. Ads</h3>
            <ol>
              <li>Click <strong>Ads</strong>.</li>
              <li>Click <strong>Add Ad</strong>.</li>
              <li>Add the image, collection name, discount text, and link.</li>
              <li>Double-check the spelling because this is public-facing.</li>
            </ol>
          </div>
        </div>

        <div class="guide-section box" id="web-details">
          <div class="box-body">
            <h3>16. Web Details</h3>
            <ol>
              <li>Click <strong>Web Details</strong>.</li>
              <li>Click <strong>Add Web Detail</strong> if you are setting the site profile for the first time.</li>
              <li>Use this page to manage business name, phone number, email, address, and description.</li>
              <li>Keep this page correct because customers can see and use this information.</li>
            </ol>
          </div>
        </div>

        <div class="guide-section box" id="sync">
          <div class="box-body">
            <h3>17. Sync Basics</h3>
            <ol>
              <li>Look at the sync pill in the top bar.</li>
              <li>On the local machine, click <strong>Sync Now</strong> to send local changes first and then receive selected updates from the live server.</li>
              <li>Click <strong>Retry Failed</strong> if some sync items failed earlier.</li>
              <li>Click <strong>Repair IDs</strong> only when you were told to do it for a sync problem.</li>
              <li>If the top bar says there is a problem, do not ignore it. Check it before the end of the day.</li>
            </ol>
            <p class="guide-note">
              Offline sales belong to the local client machine. Keep syncing regularly so other approved data stays fresh.
            </p>
          </div>
        </div>

        <div class="guide-section box" id="mistakes">
          <div class="box-body">
            <h3>18. Common Mistakes To Avoid</h3>
            <ul>
              <li>Do not create the same customer twice.</li>
              <li>Do not confirm a bank payment before you truly verify it.</li>
              <li>Do not leave payments unrecorded after a customer pays.</li>
              <li>Do not skip sync for many days on the local machine.</li>
              <li>Do not archive a product by mistake when you only wanted to edit it.</li>
              <li>Do not rush CSV imports without checking the result after import.</li>
            </ul>
          </div>
        </div>

        <div class="guide-section box" id="troubleshooting">
          <div class="box-body">
            <h3>19. Troubleshooting</h3>
            <ul>
              <li>If camera scan is not working, try <strong>Fallback Picker</strong>. Camera scan usually needs HTTPS or localhost.</li>
              <li>If a customer cannot log in, go to <strong>Customers</strong> and use <strong>Enable Login</strong> or <strong>Reset Password</strong>.</li>
              <li>If a payment is missing from a statement, open <strong>Payments</strong> and confirm the payment was saved.</li>
              <li>If sync is failing, try <strong>Retry Failed</strong>. If the issue stays, tell the admin before closing for the day.</li>
              <li>If a wrong product is in an offline sale, correct it immediately before more payments are attached.</li>
            </ul>
          </div>
        </div>

        <div class="guide-section box" id="end-of-day">
          <div class="box-body">
            <h3>20. End Of Day Checklist</h3>
            <ol>
              <li>Confirm all new online and offline sales were entered correctly.</li>
              <li>Confirm all offline payments were recorded.</li>
              <li>Check any balances that still need customer follow-up.</li>
              <li>Click <strong>Sync Now</strong> on the local machine.</li>
              <li>Make sure the sync pill does not show a problem before you close.</li>
              <li>Check tomorrow’s urgent deliveries or unpaid balances if needed.</li>
            </ol>
          </div>
        </div>
      </section>
    </div>

    <?php include 'footer.php'; ?>
  </div>

  <?php include 'scripts.php'; ?>
  <style>
    .guide-page {
      max-width: 1160px;
    }

    .guide-hero .box-body {
      padding: 28px;
    }

    .guide-eyebrow {
      margin: 0 0 8px;
      text-transform: uppercase;
      letter-spacing: .08em;
      font-size: 12px;
      font-weight: 800;
      color: #0f766e;
    }

    .guide-hero h2 {
      margin: 0 0 10px;
      font-size: 30px;
      font-weight: 800;
      color: #0f172a;
    }

    .guide-intro {
      max-width: 820px;
      font-size: 15px;
      line-height: 1.7;
      color: #334155;
      margin: 0 0 10px;
    }

    .guide-jump-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 10px;
    }

    .guide-jump-grid a {
      display: block;
      padding: 10px 12px;
      border-radius: 10px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      color: #0f172a;
      font-weight: 700;
      text-decoration: none;
    }

    .guide-jump-grid a:hover {
      background: #ecfeff;
      border-color: #99f6e4;
      color: #115e59;
    }

    .guide-section .box-body {
      padding: 22px 24px;
    }

    .guide-section h3 {
      margin: 0 0 14px;
      font-size: 22px;
      font-weight: 800;
      color: #0f172a;
    }

    .guide-section ol,
    .guide-section ul {
      margin: 0;
      padding-left: 20px;
      color: #334155;
      line-height: 1.8;
      font-size: 15px;
    }

    .guide-note {
      margin: 16px 0 0;
      padding: 12px 14px;
      border-left: 4px solid #0f766e;
      background: #f0fdfa;
      color: #134e4a;
      font-size: 14px;
      line-height: 1.7;
      border-radius: 8px;
    }

    @media (max-width: 767px) {
      .guide-hero .box-body,
      .guide-section .box-body {
        padding: 18px;
      }

      .guide-hero h2 {
        font-size: 24px;
      }

      .guide-section h3 {
        font-size: 20px;
      }
    }
  </style>
</body>

</html>
