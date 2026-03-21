<?php
include 'session.php';

$year = (int)($_GET['year'] ?? date('Y'));
if ($year < 2015 || $year > 2100) {
  $year = (int)date('Y');
}
$defaultStart = sprintf('%04d-01-01', $year);
$defaultEnd = sprintf('%04d-12-31', $year);

$startDate = trim((string)($_GET['start_date'] ?? $defaultStart));
$endDate = trim((string)($_GET['end_date'] ?? $defaultEnd));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
  $startDate = $defaultStart;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
  $endDate = $defaultEnd;
}
if ($startDate > $endDate) {
  $tmp = $startDate;
  $startDate = $endDate;
  $endDate = $tmp;
}

include 'header.php';
?>

<body>
  <div class="wrapper">
    <?php include 'navbar.php'; ?>
    <?php include 'menubar.php'; ?>

    <div class="content-wrapper">
      <section class="content-header">
        <h1>Dashboard</h1>
        <ol class="breadcrumb">
          <li><a href="home"><i class="fa fa-home"></i> Home</a></li>
          <li class="active">Dashboard</li>
        </ol>
      </section>

      <section class="content">
        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-warning"></i> Error</h4>
            <?php echo e($_SESSION['error']); ?>
          </div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-check"></i> Success</h4>
            <?php echo e($_SESSION['success']); ?>
          </div>
          <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="admin-toolbar">
          <form id="dashboardFilters" class="row" style="margin:0;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="min-width:170px;">
              <label for="year">Year</label>
              <select id="year" name="year" class="form-control">
                <?php for ($i = ((int)date('Y') - 6); $i <= ((int)date('Y') + 1); $i++): ?>
                  <option value="<?php echo $i; ?>" <?php echo ($i === $year) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="form-group" style="min-width:190px;">
              <label for="start_date">Start Date</label>
              <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo e($startDate); ?>">
            </div>
            <div class="form-group" style="min-width:190px;">
              <label for="end_date">End Date</label>
              <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo e($endDate); ?>">
            </div>
            <div class="form-group">
              <button type="submit" class="btn btn-primary">Apply Filters</button>
            </div>
          </form>
        </div>

        <div class="admin-grid admin-grid-4" id="kpiCards">
          <div class="kpi-card"><div class="kpi-card-label">Total Revenue</div><div class="kpi-card-value" id="kpi_total_revenue">-</div><div class="kpi-card-hint">Combined Online & Offline</div></div>
          <div class="kpi-card"><div class="kpi-card-label">Offline Collected</div><div class="kpi-card-value" id="kpi_offline_collected" style="color: #0f766e;">-</div><div class="kpi-card-hint">Total payments received</div></div>
          <div class="kpi-card"><div class="kpi-card-label">Outstanding Balance</div><div class="kpi-card-value" id="kpi_offline_pending" style="color: #be123c;">-</div><div class="kpi-card-hint">Total debt (Offline)</div></div>
          <div class="kpi-card"><div class="kpi-card-label">Revenue Today</div><div class="kpi-card-value" id="kpi_revenue_today">-</div><div class="kpi-card-hint">Current day</div></div>
        </div>
        
        <div class="admin-grid admin-grid-3" style="margin-top:16px;">
          <div class="kpi-card"><div class="kpi-card-label">Total Orders</div><div class="kpi-card-value" id="kpi_total_orders">-</div><div class="kpi-card-hint">All placed orders</div></div>
          <div class="kpi-card"><div class="kpi-card-label">Total Products</div><div class="kpi-card-value" id="kpi_total_products">-</div><div class="kpi-card-hint">Catalog size</div></div>
          <div class="kpi-card"><div class="kpi-card-label">Total Users</div><div class="kpi-card-value" id="kpi_total_users">-</div><div class="kpi-card-hint">Registered customers</div></div>
        </div>

        <div class="admin-grid admin-grid-2" style="margin-top:16px;">
          <div class="chart-card">
            <h3 class="chart-title">Revenue Trend: Online vs Offline</h3>
            <div id="chartMonthlyRevenue"></div>
          </div>
          <div class="chart-card">
            <h3 class="chart-title">Monthly Order Count</h3>
            <div id="chartMonthlyOrders"></div>
          </div>
          <div class="chart-card">
            <h3 class="chart-title">Top 10 Products by Revenue</h3>
            <div id="chartTopProducts"></div>
          </div>
          <div class="chart-card">
            <h3 class="chart-title">Sales by Category Share</h3>
            <div id="chartCategoryShare"></div>
          </div>
        </div>
      </section>
    </div>

    <?php include 'footer.php'; ?>
  </div>

  <?php include 'scripts.php'; ?>
  <script>
    (function() {
      var charts = {
        monthlyRevenue: null,
        monthlyOrders: null,
        topProducts: null,
        categoryShare: null
      };
      var buildAdminUrl = function(path, params) {
        var target = (window.adminUrl && typeof window.adminUrl === 'function')
          ? window.adminUrl(path)
          : path;
        var url = new URL(target, window.location.origin);
        if (params && typeof params === 'object') {
          Object.keys(params).forEach(function(key) {
            url.searchParams.set(key, params[key]);
          });
        }
        return url;
      };

      function formatMoney(value) {
        var amount = Number(value || 0);
        return 'NGN ' + amount.toLocaleString('en-NG', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });
      }

      function formatInt(value) {
        return Number(value || 0).toLocaleString('en-NG');
      }

      function setKPIs(cards) {
        document.getElementById('kpi_total_revenue').textContent = formatMoney(cards.total_revenue);
        document.getElementById('kpi_revenue_today').textContent = formatMoney(cards.revenue_today);
        document.getElementById('kpi_total_orders').textContent = formatInt(cards.total_orders);
        document.getElementById('kpi_total_products').textContent = formatInt(cards.total_products);
        document.getElementById('kpi_total_users').textContent = formatInt(cards.total_users);
        
        if(document.getElementById('kpi_offline_collected')) {
            document.getElementById('kpi_offline_collected').textContent = formatMoney(cards.offline_collected);
        }
        if(document.getElementById('kpi_offline_pending')) {
            document.getElementById('kpi_offline_pending').textContent = formatMoney(cards.offline_pending);
        }
      }

      function createOrUpdateChart(instanceKey, selector, options) {
        if (charts[instanceKey]) {
          charts[instanceKey].updateOptions(options, true, true);
          return;
        }
        charts[instanceKey] = new ApexCharts(document.querySelector(selector), options);
        charts[instanceKey].render();
      }

      function renderCharts(payload) {
        createOrUpdateChart('monthlyRevenue', '#chartMonthlyRevenue', {
          chart: { type: 'area', height: 300, toolbar: { show: false } },
          series: [
            { name: 'Online Revenue', data: payload.monthly_revenue.series_online || [] },
            { name: 'Offline Revenue', data: payload.monthly_revenue.series_offline || [] }
          ],
          xaxis: { categories: payload.monthly_revenue.labels || [] },
          colors: ['#0ea5e9', '#0f766e'],
          stroke: { curve: 'smooth', width: 3 },
          dataLabels: { enabled: false },
          yaxis: {
            labels: {
              formatter: function(val) { return 'NGN ' + Number(val || 0).toLocaleString('en-NG'); }
            }
          }
        });

        createOrUpdateChart('monthlyOrders', '#chartMonthlyOrders', {
          chart: { type: 'bar', height: 300, toolbar: { show: false } },
          series: [{ name: 'Orders', data: payload.monthly_orders.series || [] }],
          xaxis: { categories: payload.monthly_orders.labels || [] },
          colors: ['#1d4ed8'],
          dataLabels: { enabled: false }
        });

        var topLabels = [];
        var topSeries = [];
        (payload.top_products || []).forEach(function(item) {
          topLabels.push(item.name || 'Unknown');
          topSeries.push(item.revenue || 0);
        });

        createOrUpdateChart('topProducts', '#chartTopProducts', {
          chart: { type: 'bar', height: 320, toolbar: { show: false } },
          plotOptions: { bar: { horizontal: true, borderRadius: 5 } },
          series: [{ name: 'Revenue', data: topSeries }],
          xaxis: { categories: topLabels },
          colors: ['#0ea5e9'],
          dataLabels: { enabled: false }
        });

        var catLabels = [];
        var catSeries = [];
        (payload.category_sales || []).forEach(function(item) {
          catLabels.push(item.category || 'Uncategorized');
          catSeries.push(item.revenue || 0);
        });

        createOrUpdateChart('categoryShare', '#chartCategoryShare', {
          chart: { type: 'donut', height: 320 },
          series: catSeries.length ? catSeries : [1],
          labels: catLabels.length ? catLabels : ['No data'],
          legend: { position: 'bottom' },
          colors: ['#0f766e', '#0ea5e9', '#f59e0b', '#ef4444', '#8b5cf6', '#10b981', '#475569']
        });
      }

      function fetchMetrics(params) {
        return fetch(buildAdminUrl('dashboard_metrics.php', params).toString(), {
          credentials: 'same-origin'
        }).then(function(resp) {
          return resp.json();
        });
      }

      function currentFilters() {
        return {
          year: document.getElementById('year').value,
          start_date: document.getElementById('start_date').value,
          end_date: document.getElementById('end_date').value
        };
      }

      function syncUrl(filters) {
        window.history.replaceState({}, '', buildAdminUrl('home.php', filters).toString());
      }

      function loadDashboard() {
        var filters = currentFilters();
        fetchMetrics(filters).then(function(payload) {
          if (!payload || !payload.success) {
            return;
          }
          setKPIs(payload.cards || {});
          renderCharts(payload);
          syncUrl(filters);
        }).catch(function() {
          // Keep silent to avoid disruptive alert loops.
        });
      }

      document.getElementById('dashboardFilters').addEventListener('submit', function(event) {
        event.preventDefault();
        loadDashboard();
      });

      loadDashboard();
    })();
  </script>
</body>

</html>
