<aside id="crm-ns-sidebar" aria-hidden="true">
    <div style="display:flex;align-items:center;gap:10px;padding:4px 6px 16px;border-bottom:1px solid #e7ebef;">
        <div style="width:34px;height:34px;border-radius:4px;background:#337ab7;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;">
            NS
        </div>
        <div>
            <div style="font-size:13px;font-weight:700;color:#1f2a33;line-height:1.2;">NS CONSEIL</div>
            <div style="font-size:11px;color:#6e7b87;margin-top:2px;">CRM Partenaires</div>
        </div>
    </div>

    <nav style="padding:14px 0;display:flex;flex-direction:column;gap:2px;font-size:13px;">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [
            ['label' => 'Tableau de bord', 'active' => true],
            ['label' => 'Partenaires', 'active' => false],
            ['label' => 'Prospects', 'active' => false],
            ['label' => 'Opportunites', 'active' => false],
            ['label' => 'Clients', 'active' => false],
            ['label' => 'Rendez-vous', 'active' => false],
            ['label' => 'Rapports', 'active' => false],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="display:flex;align-items:center;gap:9px;min-height:32px;padding:6px 8px;border-radius:4px;color:<?php echo e($item['active'] ? '#1f5f93' : '#2f3b45'); ?>;background:<?php echo e($item['active'] ? '#e8edf2' : 'transparent'); ?>;border-left:<?php echo e($item['active'] ? '3px solid #337ab7' : '3px solid transparent'); ?>;">
                <span style="width:8px;height:8px;border-radius:2px;background:<?php echo e($item['active'] ? '#337ab7' : '#c4ccd4'); ?>;display:inline-block;"></span>
                <span><?php echo e($item['label']); ?></span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </nav>

    <div style="margin-top:auto;padding:12px 8px;border-top:1px solid #e7ebef;color:#6e7b87;font-size:11px;line-height:1.5;">
        <div style="font-weight:700;color:#566471;margin-bottom:4px;">Acces securise</div>
        <div>Connexion locale au CRM AOPIA / LIKE Formation.</div>
    </div>
</aside>
<?php /**PATH C:\laragon\www\crmfilament\resources\views/filament/ns-conseil/auth/login-sidebar.blade.php ENDPATH**/ ?>