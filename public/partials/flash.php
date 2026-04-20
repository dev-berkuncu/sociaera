<?php
/**
 * Flash Mesaj Gösterici
 */
$flash = Auth::getFlash();
if ($flash): ?>
<div class="flash-message flash-<?php echo escape($flash['type']); ?>" id="flashMessage">
    <div class="flash-content">
        <?php if ($flash['type'] === 'success'): ?>
            <i class="bi bi-check-circle-fill"></i>
        <?php elseif ($flash['type'] === 'error'): ?>
            <i class="bi bi-exclamation-circle-fill"></i>
        <?php else: ?>
            <i class="bi bi-info-circle-fill"></i>
        <?php endif; ?>
        <span><?php echo escape($flash['message']); ?></span>
    </div>
    <button class="flash-close" onclick="this.closest('.flash-message').remove()">
        <i class="bi bi-x-lg"></i>
    </button>
</div>
<script>setTimeout(() => { const el = document.getElementById('flashMessage'); if (el) el.classList.add('flash-hide'); }, 4000);</script>
<?php endif; ?>
