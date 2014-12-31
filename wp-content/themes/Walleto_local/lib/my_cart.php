<?php
function walleto_cart_area_function()
{
	
	if(isset($_GET['remove_from_cart']))
	{
		$pids = $_GET['remove_from_cart'];
		$cart = $_SESSION['my_cart'];
		$i = 0;
		
		foreach($cart as $itm)
		{
			if($itm['pid'] == $pids)  { unset($_SESSION['my_cart'][$i]);  break; }	
			
			$i++;
		}
		
		echo '<div class="saved-thing"><div class="padd10">'.__('Cart content updated.','Walleto').'</div></div>';
	}
	

	if(isset($_POST['update_card']))
	{
		$i=0;
		$cart = $_SESSION['my_cart']; $crt = array();
		
		if(is_array($_POST['cart_id_c']))
		{
			foreach($_POST['cart_id_c'] as $itm)
			{
				$crt[$i]['pid'] = $itm;
				$crt[$i]['quant'] = $_POST['cart_quant_' . $itm];
				$i++;
			}
			
			$_SESSION['my_cart'] = $crt;
		}
		
		echo '<div class="saved-thing"><div class="padd10">'.__('Cart content updated.','Walleto').'</div></div>';
		
	}
	
	
	?>
    
   

	
		
 
               <section class="shopping">
            	<h2><?php echo sprintf(__("My Cart Content",'Walleto')); ?></h2>
               
                
                <?php
				
		$cart 		= $_SESSION['my_cart']; $prc_t = 0; 
		$cart_id 	= get_option('Walleto_shopping_cart_page_id');
		$shp = 0;
		
		if(is_array($cart) and count($cart) > 0)
		{
		?>
		
						
		<?php	echo '<form method="post" action="'.get_permalink($cart_id).'">'; ?>
		<div class="responsive_table">
		<table class="shopping_cart" width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr class="head">
		  <td width="45%">Product Name & Details</td>
		  <td width="15%">Quantity</td>
		  <td width="15%">Unit Price</td>
		  
		  <td width="25%">Edit / Remove</td>
		</tr>
		<?php	
			foreach($cart as $item)
			{	
				$post_au 	= get_post($item['pid']);  
				$pp 		= Walleto_get_item_price($item['pid']);
				$prc 		= $item['quant'] * $pp;
				$shp		+= get_post_meta($item['pid'], 'shipping', true);
				$prc_t += $prc;
				$digital_good = get_post_meta($item['pid'],'digital_good',true);
				
				echo '<tr> <input type="hidden" name="cart_id_c[]" value="'.$item['pid'].'" />';
				echo '<td><a href="'.get_permalink($item['pid']).'">'. Walleto_get_first_post_image($item['pid'], 80, 80, 'img_class') .
				'<span>'. $post_au->post_title .'</a></span></td>';
				echo '<td><input type="'.($digital_good == "1" ? "hidden" : "text").'" size="4" class="do_input" name="cart_quant_'.$item['pid'].'" value="'.$item['quant'].'" /><span>Piece</span></td>';
				echo '<td><b>'.Walleto_get_show_price($prc,2).'</b></td>';
				echo '<td><a class="remove-cart" href="'.walleto_get_remove_from_cart_link($item['pid']).'">'.__('Remove from Cart','Walleto'). '</a>
				</tr>';
				
				
				
				
			}
		
				
		echo '</table>
		<section class="gtotal">
        	<button value="" name="" type="button" class="remove_all red-txt">View Details<i class="icon-double-angle-right"></i></button>
			<aside class="gtotal_main">
			   
			    <p>Shipping Cost:<span>'.Walleto_get_show_price($shp, 2).'</span></p>
			    <p><b>All Total:</b><span><b class="red-txt">'.Walleto_get_show_price(($prc_t + $shp), 2).'</b></span></p>
				<input class="btns_action" type="submit" name="continue_shopping_me" value="'.__('Continue Shopping','Walleto'). '" />
				<input class="btns_action" type="submit" name="update_card" value="'.__('Update Cart','Walleto'). '" />
				<input class="btns_action" type="submit" name="send_me_to_checkout" value="'.__('Checkout','Walleto'). '" />
			</aside>
		</section></div></form>';
			}
			else
			{
				echo __('There are no items in your shopping cart.', 'Walleto');	
			}
			
		?>
                
                
               
                </section>
          
<?php	} ?>