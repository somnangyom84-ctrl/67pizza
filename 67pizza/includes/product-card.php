<?php
/** @var array $p product row (from get_products / cart) */
$pid = (int) $p['id'];
$fav = is_favorite($pid);
?>
<div class="product-card" data-product-id="<?= $pid ?>">
  <div class="product-media">
    <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>">
      <img src="<?= e(image_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= placeholder_url() ?>'">
    </a>
    <button type="button" class="fav-btn js-fav <?= $fav ? 'active' : '' ?>" data-id="<?= $pid ?>" aria-label="Toggle favorite" title="<?= $fav ? 'Remove from favorites' : 'Add to favorites' ?>">
      <i class="bi <?= $fav ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
    </button>
    <?php if (!empty($p['is_featured'])): ?><span class="chip chip-featured"><i class="bi bi-fire"></i> Popular</span><?php endif; ?>
    <?php if (empty($p['is_available'])): ?><span class="sold-out">Unavailable</span><?php endif; ?>
  </div>
  <div class="product-body">
    <div class="product-meta"><?= e($p['category_name'] ?? '') ?>
      <?php if (!empty($p['avg_rating'])): ?> · <i class="bi bi-star-fill text-brand"></i> <?= e($p['avg_rating']) ?><?php endif; ?>
    </div>
    <h3 class="product-title"><a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>"><?= e($p['name']) ?></a></h3>
    <div class="product-footer">
      <span class="product-price"><?= format_price($p['price']) ?></span>
      <?php if (!empty($p['is_available'])): ?>
      <button type="button" class="add-btn js-add-cart" data-id="<?= $pid ?>" aria-label="Add to cart" title="Add to cart"><i class="bi bi-plus-lg"></i></button>
      <?php endif; ?>
    </div>
  </div>
</div>
