<?php
/**
 * NEXU HOSTING - Admin: Registro de Auditoría
 */
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAdmin();

$logs     = AuditModel::getRecent(200);
$page_title = 'Registro de Auditoría';
?>
<?php require_once dirname(__DIR__) . '/views/partials/head.php'; ?>
<div class="min-h-screen flex">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 min-w-0 pt-4 pb-12 px-4 sm:px-8 ml-0 lg:ml-64">
  <div class="max-w-7xl mx-auto">

    <div class="py-6 animate-fade-in">
      <h1 class="text-2xl font-extrabold text-white">Registro de Auditoría</h1>
      <p class="text-gray-400 text-sm mt-1">Últimas 200 acciones registradas en el sistema.</p>
    </div>

    <div class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-surface-border">
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Acción</th>
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Entidad</th>
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Usuario</th>
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">IP</th>
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Datos</th>
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Fecha</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-border">
            <?php foreach ($logs as $log): ?>
            <?php
              // Color-code actions
              $actionColor = 'text-gray-400';
              if (str_contains($log['action'], 'login'))    $actionColor = 'text-cyan-400';
              if (str_contains($log['action'], 'approved')) $actionColor = 'text-emerald-400';
              if (str_contains($log['action'], 'rejected')) $actionColor = 'text-red-400';
              if (str_contains($log['action'], 'created'))  $actionColor = 'text-brand-400';
              if (str_contains($log['action'], 'suspended'))$actionColor = 'text-orange-400';
            ?>
            <tr class="hover:bg-surface-hover transition-colors">
              <td class="px-5 py-3">
                <code class="text-xs font-mono <?= $actionColor ?>"><?= e($log['action']) ?></code>
              </td>
              <td class="px-5 py-3 text-xs text-gray-500 font-mono">
                <?= e($log['entity'] ?: '—') ?><?= $log['entity_id'] ? ' #'.$log['entity_id'] : '' ?>
              </td>
              <td class="px-5 py-3 text-xs text-gray-400">
                <?= e($log['username'] ?? 'sistema') ?>
              </td>
              <td class="px-5 py-3 text-xs font-mono text-gray-500">
                <?= e($log['ip_address'] ?? '—') ?>
              </td>
              <td class="px-5 py-3 max-w-xs">
                <?php
                $newVal = $log['new_value'] ? json_decode($log['new_value'], true) : null;
                if ($newVal):
                  $str = '';
                  foreach (array_slice($newVal, 0, 3) as $k => $v) {
                      $str .= "<span class='text-gray-600'>$k:</span> <span class='text-gray-400'>" . e(is_array($v) ? json_encode($v) : (string)$v) . "</span>  ";
                  }
                ?>
                <span class="text-xs font-mono truncate block max-w-xs" title="<?= e(json_encode($newVal)) ?>"><?= $str ?></span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3 text-xs text-gray-500 whitespace-nowrap">
                <?= formatDateTime($log['created_at']) ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
            <tr>
              <td colspan="6" class="px-5 py-12 text-center text-gray-500">No hay registros aún.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>
</div>
<?php require_once dirname(__DIR__) . '/views/partials/footer.php'; ?>
