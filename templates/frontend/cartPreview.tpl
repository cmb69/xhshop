<div id="xhsCartPreview">
	<form method="get">
		<input type="hidden" name="selected" value="%XHS_URL%">
		<button class="xhsShopButton xhsCrt" title="<?php echo $this->count?> <?php echo $this->label('products'); ?> ⇒ <?php $this->label('go_to_cart'); ?>"><span class="fa fa-shopping-cart fa-2x"></span><span class="xhsItemCounter xhsBadge"><?php echo $this->count; ?></span></button>
		<input type = "hidden" name= "xhsCheckout" value="cart">
	</form>
</div>
