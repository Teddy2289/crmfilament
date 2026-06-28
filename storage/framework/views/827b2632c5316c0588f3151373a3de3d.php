

<style>
    [wire\:loading-overlay] {
        display: none;
    }
</style>

<div wire:loading.flex wire:loading.delay.shortest style="display:none"
    class="fixed inset-0 z-[9999] items-center justify-center">
    
    <div class="absolute inset-0 bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm"></div>

    
    <div
        class="relative flex flex-col items-center gap-4 rounded-2xl bg-white dark:bg-gray-800 shadow-2xl px-10 py-8 border border-gray-100 dark:border-gray-700">
        <?php if (isset($component)) { $__componentOriginalbef7c2371a870b1887ec3741fe311a10 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbef7c2371a870b1887ec3741fe311a10 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.loading-indicator','data' => ['class' => 'h-10 w-10 text-primary-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::loading-indicator'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-10 w-10 text-primary-600']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbef7c2371a870b1887ec3741fe311a10)): ?>
<?php $attributes = $__attributesOriginalbef7c2371a870b1887ec3741fe311a10; ?>
<?php unset($__attributesOriginalbef7c2371a870b1887ec3741fe311a10); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbef7c2371a870b1887ec3741fe311a10)): ?>
<?php $component = $__componentOriginalbef7c2371a870b1887ec3741fe311a10; ?>
<?php unset($__componentOriginalbef7c2371a870b1887ec3741fe311a10); ?>
<?php endif; ?>
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 tracking-wide">
            Chargement…
        </span>
    </div>
</div><?php /**PATH C:\laragon\www\crmfilament\resources\views/filament/loading-overlay.blade.php ENDPATH**/ ?>