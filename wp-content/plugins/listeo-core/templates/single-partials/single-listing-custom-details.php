<!-- Main Details -->
<?php

$type = get_post_meta($post->ID, '_listing_type', true);

$details_list = Listeo_Core_Meta_Boxes::meta_boxes_custom();

$class = (isset($data->class)) ? $data->class : 'listing-details';
$output = '';
?>


<?php
if (isset($details_list['fields'])) :
	foreach ($details_list['fields'] as $detail => $value) {

		if (isset($value['icon']) && !empty($value['icon'])) {
			$check_if_im = substr($value['icon'], 0, 3);
			if ($check_if_im == 'im ') {
				$icon = ' <i class="' . esc_attr($value['icon']) . '"></i>';
			} else {
				$icon = ' <i class="fa ' . esc_attr($value['icon']) . '"></i>';
			}
		} else {
			$icon = '<i class="fas fa-check"></i>';
		}

		if (in_array($value['type'], array('select_multiple', 'multicheck_split', 'multicheck'))) {
			$meta_value = get_post_meta($post->ID, $value['id'], false);
		} else {
			$meta_value = get_post_meta($post->ID, $value['id'], true);
		};

		if ($meta_value == 'check_on' || $meta_value == 'on') {
			$default_value = isset($value['default']) ? $value['default'] : esc_html__('Yes', 'listeo_core');
			$output .= '<li class="checkboxed single-property-detail-' . $value['id'] . '"><div class="single-property-detail-label-' . $value['id'] . '">' . $value['name'] . ': ' . esc_html($default_value) . '</div></li>';
		} else {
			if (!empty($meta_value)) {
				if ($value['type'] == 'datetime' || in_array($value['id'], array('_event_date', '_event_date_end'))) {

					$meta_value_date = explode(' ', $meta_value, 2);
					$date_format = get_option('date_format');

					// Try to create DateTime object with improved error handling
					try {
						$php_format = listeo_date_time_wp_format_php();
						$date_obj = DateTime::createFromFormat($php_format, $meta_value_date[0]);
						
						if ($date_obj === false) {
							// Fallback: try with strtotime if DateTime::createFromFormat fails
							$meta_value_stamp = strtotime($meta_value_date[0]);
							$meta_value = date_i18n(get_option('date_format'), $meta_value_stamp);
						} else {
							$meta_value_stamp = $date_obj->getTimestamp();
							$meta_value = date_i18n(get_option('date_format'), $meta_value_stamp);
						}

						// Handle time part with improved parsing
						if (isset($meta_value_date[1]) && !empty($meta_value_date[1])) {
							$time_part = trim($meta_value_date[1]);
							
							// Check if it's a time range (contains '-')
							if (strpos($time_part, '-') !== false) {
								$time_range = explode('-', $time_part);
								$start_time = trim($time_range[0]);
								$end_time = isset($time_range[1]) ? trim($time_range[1]) : '';
								
								$meta_value .= esc_html__(' from ', 'listeo_core');
								$meta_value .= date_i18n(get_option('time_format'), strtotime($start_time));
								
								if (!empty($end_time)) {
									$meta_value .= esc_html__(' to ', 'listeo_core');
									$meta_value .= date_i18n(get_option('time_format'), strtotime($end_time));
								}
							} else {
								// Single time
								$meta_value .= esc_html__(' at ', 'listeo_core');
								$meta_value .= date_i18n(get_option('time_format'), strtotime($time_part));
							}
						}
						
					} catch (Exception $e) {
						// Final fallback: try to display at least the raw date if all parsing fails
						$meta_value = isset($meta_value_date[0]) ? $meta_value_date[0] : $meta_value;
						if (isset($meta_value_date[1]) && !empty($meta_value_date[1])) {
							$meta_value .= ' ' . $meta_value_date[1];
						}
					}

				}
			}
			if (in_array($value['id'], array('_id', '_ID', '_Id'))) {
				$meta_value = apply_filters('listeo_listing_id', $post->ID);
			}



			if (!empty($meta_value)) {
				
				//echo "tu jestesmy ".$value['id'].' '.$value['type'].' <br>';
				if ($value['id'] == '_area') {
					$scale = get_option('listeo_scale', 'sq ft');
					if (filter_var($meta_value, FILTER_VALIDATE_URL) !== false) {

						$meta_value = '<a href="' . esc_url($meta_value) . '" target="_blank">' . esc_url($meta_value) . '</a>';
					}
					if (isset($value['invert']) && $value['invert'] == true) {

						$output .= '<li class="main-detail-' . $value['id'] . '">' . $icon . apply_filters('listeo_scale', $scale) . ' <span>' . $meta_value . '</span> </li>';
					} else {
						$output .= '<li class="main-detail-' . $value['id'] . '">' . $icon . '<span>' . $meta_value . '</span> ' . apply_filters('listeo_scale', $scale) . ' </li>';
					}
				} else if ($details_list['fields'][$detail]['type'] == 'file') {
					$output .= '<li class="main-detail-' . $value['id'] . ' listeo-download-detail"> '. $icon . ' <a href="' . $meta_value . '" /> ' . esc_html__('Download', 'listeo_core') . ' ' . wp_basename($meta_value) . ' </a></li>';
				} else {
					if (filter_var($meta_value, FILTER_VALIDATE_URL) !== false) {

						$meta_value = '<a href="' . esc_url($meta_value) . '" target="_blank">' . esc_url($meta_value) . '</a>';
					}
			
					if (isset($details_list['fields'][$detail]['options']) && !empty($details_list['fields'][$detail]['options'])) {

						if (is_array($meta_value) && !empty($meta_value)) {


							if (isset($value['invert']) && $value['invert'] == true) {
								$output .= '<li class="main-detail-' . $value['id'] . '">' . $icon . '<span>';
								$i = 0;
								$last = count($meta_value);


								foreach ($meta_value as $key => $saved_value) {
									$i++;
									if (isset($details_list['fields'][$detail]['options'][$saved_value]))
										$output .= $details_list['fields'][$detail]['options'][$saved_value];
									if ($i >= 1 && $i < $last) : $output .= ", ";
									endif;
								}
								$output .= '</span> <div class="single-property-detail-label-' . $value['id'] . '">' . $value['name'] . '</div> </li>';
							} else {

								$output .= '<li class="main-detail-' . $value['id'] . '">' . $icon . '<div class="single-property-detail-label-' . $value['id'] . '">' . $value['name'] . '</div> <span>';

								$i = 0;


								// if(!empty($meta_value) && $details_list['fields'][$detail]['type'] == 'select_multiple') {
								// 	$meta_value = $meta_value[0];

								// }
								$last = count($meta_value);

								foreach ($meta_value as $key => $saved_value) {
									
									$i++;
									
									if ($details_list['fields'][$detail]['type'] == 'select_multiple') {

										if (isset($details_list['fields'][$detail]['options'][$saved_value]))
											$output .= $details_list['fields'][$detail]['options'][$saved_value];
										if ($i >= 0 && $i < $last) : $output .= ", ";
										endif;
									} else if ($details_list['fields'][$detail]['type'] == 'repeatable' || $details_list['fields'][$detail]['type'] == 'group') {
								
										if (is_array($saved_value)) {
											foreach ($saved_value as $_key => $_value) {
												$output .= '<dl>
													<dt>'.$details_list['fields'][$detail]['options'][$_key].'</dt>
													<dd>'.$_value.'</dd>
												</dl>';
											
						
											}
										}
										
									} else {

										if (isset($details_list['fields'][$detail]['options'][$saved_value]))
											$output .= $details_list['fields'][$detail]['options'][$saved_value];
										if ($i >= 0 && $i < $last) : $output .= ", ";
										endif;
									}
								}
								$output .= '</span></li>';
							}
						} else {

							if (isset($value['invert']) && $value['invert'] == true) {
								if (isset($details_list['fields'][$detail]['options'][$meta_value])) {
									$output .= '<li class="main-detail-' . $value['id'] . '">' . $icon . '<span>' . $details_list['fields'][$detail]['options'][$meta_value] . '</span> <div class="single-property-detail-label-' . $value['id'] . '">' . $value['name'] . '</div> </li>';
								}
							} else {
								$output .= '<li class="main-detail-' . $value['id'] . '">' . $icon . '<div class="single-property-detail-label-' . $value['id'] . '">' . $value['name'] . '</div> <span>' . $details_list['fields'][$detail]['options'][$meta_value] . '</span></li>';
							}
						}
					} else {

						if (isset($value['invert']) && $value['invert'] == true) {
							$output .= '<li class="main-detail-' . $value['id'] . '">' . $icon . '<div class="single-property-detail-label-' . $value['id'] . '">' . $value['name'] . '</div> <span>';
							$output .= (is_array($meta_value)) ? implode(",", $meta_value) : $meta_value;
							$output .= '</span></li>';
						} else {
							$output .= '<li class="main-detail-' . $value['id'] . '">' . $icon . '<span>';
							$output .= (is_array($meta_value)) ? implode(",", $meta_value) : $meta_value;
							$output .= '</span> <div class="single-property-detail-label-' . $value['id'] . '">' . $value['name'] . '</div> </li>';
						}
					}
				}
			}
		}
	}
endif;
if (!empty($output)) : ?>
		<ul class="<?php esc_attr_e($class); ?>" id="<?php esc_attr_e($class); ?>">
			<?php echo $output; ?>
		</ul>
	<?php endif; ?>