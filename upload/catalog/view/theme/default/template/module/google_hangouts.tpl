<?php if ($theme) { ?>
<div class="box">
  <div class="box-heading <?php echo $header_shape; ?> <?php echo $header_color; ?>"><?php echo $title; ?></div>
  <div class="box-content <?php echo $content_shape; ?> <?php echo $content_color; ?>" style="text-align:center;">
    <?php echo $code; ?>
  </div>
</div>
<?php } else { ?>
  <div class="<?php echo $content_shape; ?> <?php echo $content_color; ?>" style="margin-bottom:20px; text-align:center;">
    <?php echo $code; ?>
  </div>
<?php } ?>