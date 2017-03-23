<?php
//  Product Option Image PRO / Изображения опций PRO
//  Support: support@liveopencart.com / Поддержка: help@liveopencart.ru

class ModelModuleProductOptionImagePro extends Model {
  
	private $theme_name = '';
	private $installed = null;
	private $cache_product_option_settings = array();
	
  public function getThemeName() {
		
		if ($this->theme_name == '') {
		
			$theme_name = '';
			$poip_settings = $this->getSettings();
			if ( !empty($poip_settings['custom_theme_id']) && trim($poip_settings['custom_theme_id']) ) {
				$theme_name = trim($poip_settings['custom_theme_id']);
			} else {
			
				if ( VERSION >= '2.2.0.0' ) {
					if ($this->config->get('config_theme') == 'theme_default') {
						$theme_name = $this->config->get('theme_default_directory');
					} else {
						$theme_name = substr($this->config->get('config_theme'), 0, 6) == 'theme_' ? substr($this->config->get('config_theme'), 6) : $this->config->get('config_theme') ;
					}
				} else {  
					$theme_name = $this->config->get('config_template');
				}
				
				if ($theme_name == 'BurnEngine') {
					$theme_info = (array) $this->config->get( 'BurnEngine_theme');
					if ($theme_info && !empty($theme_info['id']) ) {
						$theme_name = $theme_name.'_'.$theme_info['id']; 
					}
				}
			}
			
			// shorten theme name
			$themes_shorten = array('so-shoppystore',
															'so-lovefashion',
															'OPC080185',
															'OPC080186',
															'OPC080189',
															'OPC080190',
															'OPC070162',
															'OPC070164',
															'OPC060130',
															'tt_huge',
															'tt_optima_',
															'tt_greek_',
															'aspire',
															);
			foreach ( $themes_shorten as $theme_shorten ) {
				$theme_shorten_length = strlen($theme_shorten);
				if ( substr($theme_name, 0, $theme_shorten_length) == $theme_shorten ) {
					$theme_name = substr($theme_name, 0, $theme_shorten_length);
					break;
				}
			}
			
			$this->theme_name = $theme_name;
		}
		return $this->theme_name;
  }
  
  public function installed() {
		
		//$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = 'module' AND `code` = 'product_option_image_pro'");
		//return $query->num_rows;
    
		if ( is_null($this->installed) ) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = 'module' AND `code` = 'product_option_image_pro'");
			$this->installed = $query->num_rows;
		}
    // not working in mijoshop
    //$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'poip_option_image"');
    return $this->installed;
  }
	
	public function getTemplateIncludeFileName($suffix, $default_theme=false) {
		
		if ( $default_theme ) {
			$theme_name = '_default';
		} else { // custom
			$theme_name = $this->getThemeName();
			if ($theme_name == '_default') { // reserved name for basic file
				return '';
			}
		}
		$file_name = DIR_APPLICATION.'view/extension/product_option_image_pro/'.$theme_name.'.'.$suffix.'.tpl';
		if ( file_exists($file_name) ) {
			return $file_name;
		}
		return '';
	}
  
  private function theme_config_get($setting_name) {
    // config_ in the beginning of $setting_name
    if ( VERSION >= '2.2.0.0' ) {
      return $this->config->get($this->config->get('config_theme') . substr($setting_name, 6) );
    } else { 
      return $this->config->get($setting_name);
    }
  }
	
	
	public function getPOIPIdFromRequest() {
		$poip_ov = false;
		if (isset($_GET['poip_ov'])) {
			if ($_GET['poip_ov']) {
				$poip_ov = $_GET['poip_ov'];
			}
		} elseif ( isset($_SERVER['REQUEST_URI']) ) {
		
			$poip_ov_name = "poip_ov=";
			if (strpos($_SERVER['REQUEST_URI'], $poip_ov_name) !== false) {
			
				$poip_str = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], $poip_ov_name)+strlen($poip_ov_name));
				$poip_ov = "";
				while ($poip_str != "" && strpos("0123456789", substr($poip_str, 0, 1))!==false ) {
					$poip_ov.= substr($poip_str, 0, 1);
					$poip_str = substr($poip_str, 1);
				}
				if ($poip_ov != "") {
					$poip_ov = $poip_ov;
				}
			}
		}
		return $poip_ov;
	}
	
	private function getProductMainImage($p_product_id) {
		$query = $this->db->query("SELECT `image` FROM `".DB_PREFIX."product` WHERE `product_id` = '".(int)$p_product_id."' ");
		if ( $query->num_rows ) {
			return $query->row['image'];
		}
		return '';
	}
  
  public function addMainProductImageToAdditional($product_id, $product_images) {
  //public function addMainProductImageToAdditional($product_id, &$product_images) {
  
    //$product_images = &$product_images_ref_arr[0];
    
    if ($this->installed()) {
      
      $poip_settings = $this->getSettings();
      
      if ( !empty($poip_settings['img_main_to_additional']) ) {
				
				if ( $poip_settings['img_main_to_additional'] == 1 || ( $poip_settings['img_main_to_additional'] == 2 && $product_images ) )
				
        // if there's not main images in list of additional images, let's add it
				$product_main_image = $this->getProductMainImage($product_id);
				if ( $product_main_image ) {
        //$product_info = $this->model_catalog_product->getProduct($product_id);
        //if (isset($product_info['image']) && trim($product_info['image']) != "" ) {
          $have_image = false;
          foreach ($product_images as $product_image) {
            if ($product_image['image'] == $product_main_image) {
              $have_image = true;
              break;
            }
          }
          if (!$have_image) {
            array_unshift($product_images, array('product_id'=>$product_id, 'image'=>$product_main_image, 'sort_order'=>0, 'product_image_id'=>"-1"));
          }
        }
      }
    }
    
    return $product_images;
    
  }
  
  // module settings
  public function getSettings() {
    
    $poip_settings = $this->config->get('poip_module');
    
    return $poip_settings;
    
  }

  
  public function getSettingsNames($for_product=true) {
    $settings = array();
    $settings[] = "img_change";
    if (!$for_product) {
      $settings[] = "img_hover";
    }
    $settings[] = "img_use";
    $settings[] = "img_limit";
    if (!$for_product) {
      $settings[] = "img_gal";
    }
    $settings[] = "img_option";
    $settings[] = "img_category";
    $settings[] = "img_first";
    $settings[] = "img_cart";
    $settings[] = "img_radio_checkbox";
    $settings[] = "dependent_thumbnails";
    
    return $settings;
  }
  

  
  
  
  public function getOptionSettings($product_option_id) { // no cache, because cache is used on the higher level (for product option settings)
    
    if (!$this->installed()) return array();
		
		/*
		if ( !isset($this->cache_option_settings[$product_option_id]) ) {
			$query = $this->db->query(" SELECT PMOS.*, PS.product_option_id
                                FROM ".DB_PREFIX."poip_main_option_settings PMOS, ".DB_PREFIX."product_option PS
                                WHERE PS.product_option_id = ".(int)$product_option_id."
                                  AND PS.option_id = PMOS.option_id
                                ");
			if ($query->num_rows) {
				$this->cache_option_settings[$product_option_id] = $query->row;
			} else {
				$this->cache_option_settings[$product_option_id] = array();
			}
		}
		return $this->cache_option_settings[$product_option_id];
		*/
    
    $query = $this->db->query(" SELECT PMOS.*, PS.product_option_id
                                FROM ".DB_PREFIX."poip_main_option_settings PMOS, ".DB_PREFIX."product_option PS
                                WHERE PS.product_option_id = ".(int)$product_option_id."
                                  AND PS.option_id = PMOS.option_id
                                ");
    
    if ($query->num_rows) {
      return $query->row;
    }
    
    return array();
    
  }
  
  // exact (determinded) options settings
  public function getProductOptionSettings($product_option_id) {
    
    $option_settings = array();
    if (!$this->installed()) return $option_settings;
		
		if ( !isset($this->cache_product_option_settings[$product_option_id]) ) {
    
			$poip_settings = $this->config->get('poip_module');
			$poip_option_settings = $this->getOptionSettings($product_option_id);
			
			$query = $this->db->query("SELECT * FROM ".DB_PREFIX."poip_option_settings WHERE product_option_id = ".(int)$product_option_id." ");
			
			$settings_names = $this->getSettingsNames();
			
			foreach ($settings_names as $setting_name) {
				if ($query->row && isset($query->row[$setting_name]) && $query->row[$setting_name] != 0) {
					$option_settings[$setting_name] = $query->row[$setting_name]-1;
					
				} elseif (isset($poip_option_settings[$setting_name]) && $poip_option_settings[$setting_name] != 0) {
					$option_settings[$setting_name] = $poip_option_settings[$setting_name]-1;
					
				} elseif (isset($poip_settings[$setting_name])) {
					$option_settings[$setting_name] = $poip_settings[$setting_name];
					
				} else {  
					$option_settings[$setting_name] = false;
				}
			}
			
			$this->cache_product_option_settings[$product_option_id] = $option_settings;
		}
	
		return $this->cache_product_option_settings[$product_option_id];
    
  }
  
  // all product settings (for all options)
  public function getProductSettings($product_id) {
    $query = $this->db->query("SELECT product_option_id FROM ".DB_PREFIX."product_option WHERE product_id = ".(int)$product_id."  ");
    $poip_settings = array();
    foreach ($query->rows as $row) {
      $poip_settings[$row['product_option_id']] = $this->getProductOptionSettings($row['product_option_id']);
    }
    return $poip_settings;
  }
  
  
  
  // if other sort order neede, it should be options (may be needed for categories pages)
  public function getProductOptionImages($product_id, $return_table=false, $stock_control=true) {
    
    $images = array();
    
    if (!$this->installed()) return $images;
    
    $language_id = (int)$this->config->get('config_language_id');
    
    $poip_settings = $this->getProductSettings($product_id);
    
    // add standard options image into option value images list (if needed)
    $query = $this->db->query(" SELECT POV.*, OV.image, OD.name option_name, OVD.name value_name
                                FROM ".DB_PREFIX."product_option_value POV
                                    LEFT JOIN ".DB_PREFIX."option_description OD ON (POV.option_id = OD.option_id AND OD.language_id = ".$language_id.")
                                  , ".DB_PREFIX."option_value OV 
                                    LEFT JOIN ".DB_PREFIX."option_value_description OVD ON (OVD.option_value_id = OV.option_value_id AND OVD.language_id = ".$language_id.")
                                  
                                WHERE POV.option_value_id = OV.option_value_id
                                  AND POV.product_id = ".(int)$product_id."
                                ORDER BY OV.sort_order ASC  
                                ");
    $all_product_option_images = array();
    foreach ($query->rows as $row) {
      if ( isset($poip_settings[$row['product_option_id']]['img_first']) && $poip_settings[$row['product_option_id']]['img_first'] == 2 ) {
        if ($row['image'] != 'no_image.jpg' && trim($row['image']) != '' && file_exists(DIR_IMAGE.$row['image']) ) {
          $all_product_option_images[] = array( 'option_name'=>$row['option_name']
                                               ,'value_name'=>$row['value_name']
                                               ,'product_id'=>$row['product_id']
                                               ,'product_option_id'=>$row['product_option_id']
                                               ,'product_option_value_id'=>$row['product_option_value_id']
                                               ,'image'=>$row['image']
                                               ,'sort_order'=>0
                                               ,'thumb'=>$row['image']
                                               ,'option_id'=>$row['option_id']
                                               ,'option_value_id'=>$row['option_value_id']
                                               );
        }
      }
    }
    
		
		$query = $this->db->query(" SELECT POIP.*, OV.image thumb, OD.name option_name, OVD.name value_name, POV.option_id, POV.option_value_id
                                  FROM ".DB_PREFIX."poip_option_image POIP
                                      ,".DB_PREFIX."product_option_value POV
                                      ,".DB_PREFIX."option_value OV LEFT JOIN ".DB_PREFIX."option_value_description OVD ON (OVD.option_value_id = OV.option_value_id AND OVD.language_id = ".$language_id.")
                                      ,`".DB_PREFIX."option` O LEFT JOIN ".DB_PREFIX."option_description OD ON (OD.option_id = O.option_id AND OD.language_id = ".$language_id.")
                                      ,".DB_PREFIX."product_option PO
                                WHERE POIP.product_id = ".(int)$product_id."
                                  AND POIP.product_option_value_id = POV.product_option_value_id
                                  AND POV.option_value_id = OV.option_value_id
                                  AND PO.product_option_id = POIP.product_option_id
                                  AND PO.option_id = O.option_id
                                  ".($stock_control ? "AND (NOT POV.subtract OR POV.quantity > 0)" : "")."
                                ORDER BY O.sort_order ASC, POIP.product_option_id ASC, OV.sort_order ASC, POIP.product_option_value_id, POIP.sort_order ASC
                                ");
    
    $existing_images = array();
    foreach ( $query->rows as $row ) {
      if ($row['image'] != 'no_image.jpg' && trim($row['image']) != '' && file_exists(DIR_IMAGE.$row['image']) ) {
        $existing_images[] = $row;
      }
    }
    
    $all_product_option_images = array_merge($all_product_option_images, $existing_images);
    //$all_product_option_images = array_merge($all_product_option_images, $query->rows);
    
    // use first image as icon/thumb (if needed)
    $thumbs = array();
    foreach ($all_product_option_images as $row) {
      if (!isset($thumbs[$row['product_option_value_id']])) {
        if ( isset($poip_settings[$row['product_option_id']]['img_first']) && $poip_settings[$row['product_option_id']]['img_first'] ) {
          $thumbs[$row['product_option_value_id']] = $row['image'];
        } else {
          $thumbs[$row['product_option_value_id']] = $row['thumb'];
        }
      }
    }
    
    if ($return_table) return $all_product_option_images;
    
    foreach ($all_product_option_images as $row) {
      if (!isset($images[$row['product_option_id']])) {
        $images[$row['product_option_id']] = array();
      }
      if (!isset($images[$row['product_option_id']][$row['product_option_value_id']])) {
        $images[$row['product_option_id']][$row['product_option_value_id']] = array();
      }
      $images[$row['product_option_id']][$row['product_option_value_id']][] = array(  'image'=>$row['image']
                                                                                    , 'thumb'=>( (isset($thumbs[$row['product_option_value_id']]) && $thumbs[$row['product_option_value_id']]) ? $thumbs[$row['product_option_value_id']] : 'no_image.jpg')
                                                                                    , 'srt'=>$row['sort_order']
                                                                                    , 'product_option_id'=>$row['product_option_id']
                                                                                    , 'option_id'=>$row['option_id']
                                                                                    , 'option_value_id'=>$row['option_value_id']
                                                                                    , 'option_name'=> (isset($row['option_name']) && $row['option_name']) ? $row['option_name'] : ''
                                                                                    , 'value_name'=> (isset($row['value_name']) && $row['value_name']) ? $row['value_name'] : ''
                                                                                    );
    }
    
    return $images;
    
  }
  
  public function getProductOptionImagesByValues($product_id) {
    
    $images = array();
    
    if (!$this->installed()) return $images;
    
    $product_images = $this->getProductOptionImages($product_id);
    foreach ($product_images as $product_option_id => $po_images) {
      
      if ($po_images && is_array($po_images)) {
        foreach ($po_images as $product_option_value_id => $pov_images) {
          $images[$product_option_value_id] = $pov_images;
        }
      }
      
    }
    
    return $images;
    
  }
  
  // $product_images_old - standard product images array
	// $product_id
	// $product_images - empty array
  public function addOptionImagesToProductImages($product_images_old, $product_id, $product_images, $popup_width=0, $popup_height=0) {
  //public function addOptionImagesToProductImages($product_images_old, $product_id, &$product_images, $popup_width=0, $popup_height=0) {
    
    $poip_settings = $this->getSettings();
    
    $product_images = array();
    $added_images = array();
    
    $options_images = $this->getProductOptionImages($product_id, true);
    
    if ( isset($poip_settings['options_images_edit']) && $poip_settings['options_images_edit'] == 1 ) {
      // add in product images order
      
      foreach ($product_images_old as $image_old) {
        
        foreach ($options_images as $row) {
      
          if ($image_old['image'] == $row['image']) {
      
            if (!in_array($row['image'], $added_images)) {
              $product_images[] = array('product_id'=>$product_id, 'image'=>$row['image'], 'sort_order'=>$row['sort_order']);
              $added_images[] = $row['image'];
            }
            foreach ($product_images as &$image) {
              if ($image['image'] == $row['image']) {
                if (!isset($image['product_option_id'])) $image['product_option_id'] = array();
                if (!isset($image['product_option_value_id'])) $image['product_option_value_id'] = array();
                if (!in_array($row['product_option_id'], $image['product_option_id'])) {
                  $image['product_option_id'][] = $row['product_option_id'];
                }
                
                if ( !in_array($row['product_option_value_id'], $image['product_option_value_id']) ) {
                  $image['product_option_value_id'][] = $row['product_option_value_id'];
                  
                  if (isset($row['option_name']) && $row['option_name'] && isset($row['value_name']) && $row['value_name']) {
                    if (!isset($image['title'])) {
                      $image['title'] = '';
                    }
                    $image['title'] .= trim("\n".$row['option_name'].": ".$row['value_name']);
                  }
                }
                
              }
            }
            unset($image);
          }
        }  
      }
    }
    
    foreach ($options_images as $row) {
      
      if (!in_array($row['image'], $added_images)) {
        $product_images[] = array('product_id'=>$product_id, 'image'=>$row['image'], 'sort_order'=>$row['sort_order']);
        $added_images[] = $row['image'];
      }
      foreach ($product_images as &$image) {
        if ($image['image'] == $row['image']) {
          if (!isset($image['product_option_id'])) $image['product_option_id'] = array();
          if (!isset($image['product_option_value_id'])) $image['product_option_value_id'] = array();
          if (!in_array($row['product_option_id'], $image['product_option_id'])) {
            $image['product_option_id'][] = $row['product_option_id'];
          }
          
          if ( !in_array($row['product_option_value_id'], $image['product_option_value_id']) ) {
            $image['product_option_value_id'][] = $row['product_option_value_id'];
            
            if (isset($row['option_name']) && $row['option_name'] && isset($row['value_name']) && $row['value_name']) {
              if (!isset($image['title'])) {
                $image['title'] = '';
              }
              $image['title'] .= "\n".$row['option_name'].": ".$row['value_name'];
            }
          }
        }
      }
      unset($image);
      
    }
    
    
    //foreach ($product_images_old as $product_image) {
    foreach (array_reverse($product_images_old) as $product_image) {
      if (!in_array($product_image['image'], $added_images)) {
        array_unshift ($product_images, $product_image);
      }
    }
    
    $poip_product_settings = $this->getProductSettings($product_id);
    
    $results = array();
    foreach ($product_images as &$result) {
      
      if (isset($result['product_option_id']) && is_array($result['product_option_id'])) {
        $show_image = false;
        foreach ($result['product_option_id'] as $product_option_id) {
          if (isset($poip_product_settings[$product_option_id]) && $poip_product_settings[$product_option_id]['img_use']) {
            $show_image = true;
            break;
          }
        }
      } else {
        $show_image = true;
      }
      
			if ($popup_width == 0 || $popup_height == 0) {
				$result['popup'] = $this->image_resize($result['image'], $this->theme_config_get('config_image_popup_width'), $this->theme_config_get('config_image_popup_height'));
			} else {
				$result['popup'] = $this->image_resize($result['image'], $popup_width, $popup_height);
			}

      $result['thumb'] = $this->image_resize($result['image'], $this->theme_config_get('config_image_additional_width'), $this->theme_config_get('config_image_additional_height'));
      $result['main'] = $this->image_resize($result['image'], $this->theme_config_get('config_image_thumb_width'), $this->theme_config_get('config_image_thumb_height'));
      $result['option_thumb'] = $this->image_resize($result['image'], 50, 50);
      
      if ($show_image) {  
        $results[] = $result;
      }
    }
    unset($result);
		
		$results = $this->addVideoIfNeeded($product_images_old, $results);
    
    return array('results'=>$results, 'product_images'=>$product_images);
      
  }
	
	// some other modules may require 'video' element in image array
	private function addVideoIfNeeded($product_images_old, $results) {
		
		// let's think that it's always needed
		$videoNeeded = true;
		/*
		$videoNeeded = false;
		foreach ($product_images_old as $img) {
			if (isset($img['video'])) {
				$videoNeeded = true;
				break;
			}
		}
		*/
		if ($videoNeeded) {
			foreach ($results as &$result) {
				if (!isset($result['video'])) {
					$result['video'] = '';
				}
			}
			unset($result);
		}
		return $results;
	}
  
  public function image_resize($image, $width, $height) {
    
    if ( $this->getThemeName() == 'maxstore' && method_exists($this->model_tool_image, 'cropsize') ) {
      
      return $this->model_tool_image->cropsize($image, $width, $height);
      
    } else if ( class_exists('ImageWatermark') ) { // compatibility with extension WaterMark by Pentacorp
					
			return $this->model_tool_image->resize($image, $width, $height, 'product_popup');		
					
		} else {
      
      return $this->model_tool_image->resize($image, $width, $height);
      
    }
    
  }
  
  public function getProductOptionsIdsWithImages($results) {
    
    $ids = array();
    
    foreach ($results as $result) {
      if (isset($result['product_option_id']) && is_array($result['product_option_id'])) {
        foreach ($result['product_option_id'] as $product_option_id) {
          if (!in_array($product_option_id, $ids)) {
            $ids[] = $product_option_id;
          }
        }
      }
    }
    
    return $ids;
  }
	
	//public function 
	
	private function themeHasTemplateForProductLists() {
		if ( is_null($this->themeHasTemplateForProductLists) ) {
			$template_file = $this->getTemplateIncludeFileName('list');
			$this->themeHasTemplateForProductLists = $template_file ? true : false;
		}
		return $this->themeHasTemplateForProductLists;
	}
  
	public function getCategoryImagesForController($product_id, $module_setting=false) { // returns images only if it is needed (old style option images in product lists displaying - not trough additional ajax call)
		if ( !$this->themeHasTemplateForProductLists() ) {
			return $this->getCategoryImages($product_id, $module_setting);
		} else {
			return array();
		}
	}
  
  public function getCategoryImages($product_id, $module_setting=false, $return_ordered=false) { // $return_ordered currently not used
    
    if (!$this->installed()) return false;
    
    $this->load->model('tool/image');
    
    // OC 1.X old-style
    if ($module_setting && is_array($module_setting) && isset($module_setting['image_width']) && isset($module_setting['image_height'])  ) {
      $image_product_width = $module_setting['image_width'];
      $image_product_height = $module_setting['image_height'];
    
    // OC 2.0 new-style
    } elseif ($module_setting && is_array($module_setting) && isset($module_setting['width']) && isset($module_setting['height'])  ) {
      $image_product_width = $module_setting['width'];
      $image_product_height = $module_setting['height'];  
      
    } elseif ($module_setting == "related_products") {
      $image_product_width = $this->theme_config_get('config_image_related_width');
      $image_product_height = $this->theme_config_get('config_image_related_height');
    } else {
      $image_product_width = $this->theme_config_get('config_image_product_width');
      $image_product_height = $this->theme_config_get('config_image_product_height');
    }
    
    if ($this->getThemeName() == "eagency") {
      $image_product_width = MIN($image_product_width, 200);
      $image_product_height = MIN($image_product_height, 200);
    }
    
    if ( $this->getThemeName() == "marketshop" && $module_setting && isset($module_setting['name']) && $module_setting['name'] == 'bestseller' ) {
      $image_product_width = MIN($image_product_width, 50);
      $image_product_height = MIN($image_product_height, 50);
    }

    
    
    // pav fashion theme compatibility
    if ($this->getThemeName() == "pav_fashion" && (!$module_setting || $module_setting=="related_products") ) {
      $icon_width = 43;
      $icon_height = 43;
    } elseif ( $this->getThemeName() == "themegloballite" ) {
      $icon_width = 16;
      $icon_height = 16;
    } elseif ( $this->getThemeName() == "opc1000" ) {
      $icon_width = round(($image_product_width)/8-14);
      $icon_height = round(($image_product_height)/8-14);
    } elseif ( $this->getThemeName() == "moment" ) {
      $icon_width = 24;
      $icon_height = 16;
		} elseif ( $this->getThemeName() == "fastor" ) {
      $icon_width = 40;
      $icon_height = 40;		
		// Pavilion (BurnEngine)
		} elseif ( $this->getThemeName() == "BurnEngine" ) {
      $icon_width = 40;
      $icon_height = 40;
		} elseif ( substr($this->getThemeName(),0,6) == "aspire" ) {
      $icon_width = 40;
      $icon_height = 40;
			
		} elseif ( $this->getThemeName() == "pav_digitalstore" ) {
			$icon_width = 16;
      $icon_height = 16;
		} elseif ( $this->getThemeName() == "helena" ) {
			$icon_width = 40;
      $icon_height = 40;	
    } else {
      // base image size used for calculation is 120 (128 with magins/paddings), icon/thumb size is 24 (with margins/paddings 32)
      // (120)/4-6=24
      $icon_width = round(($image_product_width)/4-6);
      $icon_height = round(($image_product_height)/4-6);
    }

    $images = $this->getProductOptionImages($product_id);
    
    $settings = $this->getProductSettings($product_id);
    
    $category_images = array();
    foreach ($images as $product_option_id => $image_po) {
      if (isset($settings[$product_option_id]) && $settings[$product_option_id]['img_category']) {
        
        foreach ($image_po as $product_option_value_id => $image_pov) {
          if (count($image_pov) > 0) {
            if (!isset($category_images[$product_option_id])) {
              $category_images[$product_option_id] = array();
            }
            $image_pov[0];
						
						$category_image_data = array(	'icon'=>$this->model_tool_image->resize($image_pov[0]['image'], $icon_width, $icon_height),
																					'thumb'=>$this->model_tool_image->resize($image_pov[0]['image'], $image_product_width, $image_product_height),
																					'image'=>$image_pov[0]['image'],
																					'option_id'=>$image_pov[0]['option_id'],
																					'option_value_id'=>$image_pov[0]['option_value_id'],
																					'product_option_value_id'=>$product_option_value_id,
																				);
						if (isset($image_pov[0]['option_name']) && $image_pov[0]['option_name'] && isset($image_pov[0]['value_name']) && $image_pov[0]['value_name'] ) {
              $category_image_data['title'] = "".$image_pov[0]['option_name'].": ".$image_pov[0]['value_name'];
            }
          
						if ($return_ordered) {
							$category_images[$product_option_id][] = $category_image_data;
						} else {
							$category_images[$product_option_id][$product_option_value_id] = $category_image_data;
						}
            
          }
        }
        
      }
    }
    
    return $category_images;
    
  }
  
   
  
  public function getProductCartImage($product_id, $option_data, $image) {
    
    if (!$this->installed()) {
      return $image;
    }
    
    $selected_product_option = array();
    $selected_product_option_value = array();
    foreach ($option_data as $option_value_data) {
      if (!in_array($option_value_data['product_option_id'], $selected_product_option)) $selected_product_option[] = $option_value_data['product_option_id'];
      if (!in_array($option_value_data['product_option_value_id'], $selected_product_option_value)) $selected_product_option_value[] = $option_value_data['product_option_value_id'];
    }
    
    
    $product_images = $this->getProductOptionImages($product_id);
    if ( count($product_images) > 0 ) {
      
      $product_settings = $this->getProductSettings($product_id);
      
      $cart_options = array();
      $filter_options = array();
      foreach ($product_images as $product_option_id => $product_option_images ) {
        
        if ( in_array($product_option_id, $selected_product_option) ) { // option value is selected
          
          $images_count = 0;
          
          foreach ($product_option_images as $product_option_value => $product_option_value_images) {
            $images_count = $images_count + count($product_option_value_images);
          }
          
          if ($images_count > 0) {
            if (isset($product_settings[$product_option_id]['img_cart']) && $product_settings[$product_option_id]['img_cart']) {
              $cart_options[] = $product_option_id;
              if ($product_settings[$product_option_id]['img_limit']) $filter_options[] = $product_option_id;
            }
          }
        }
      }
      
      if (count($filter_options)>0) {
        
        $images = false;
        foreach ($product_images as $product_option_id => $product_option_images) {
          if (in_array($product_option_id, $filter_options)) {
            $current_images = array();
            foreach ($product_images[$product_option_id] as $product_option_value_id => $product_option_value_images) {
              if ( in_array($product_option_value_id, $selected_product_option_value) ) { // selected option value
                foreach ($product_option_value_images as $image_info) {
                  if (!in_array($image_info['image'], $current_images)) {
                    $current_images[] = $image_info['image'];
                  }
                }
              } 
            }
            
            if (count($current_images) > 0) {
              if ($images === false) {
                $images = $current_images;
              } else {
                $images = array_values(array_intersect($images, $current_images));
              }
            }
            
          }
        }
        
        if ($images && count($images)>0) {
          $image = $images[0];
        }
        
      } elseif (count($cart_options)>0) { // get first option image
        $product_option_id = $cart_options[0];
        $image_set = false;
        foreach ($product_images[$product_option_id] as $product_option_value_id => $product_option_value_images) {
          if ( in_array($product_option_value_id, $selected_product_option_value) ) { // selected option value
            foreach ($product_option_value_images as $image_info) {
              $image = $image_info['image'];
              $image_set = true;
              break;
            }
          }
          if ( $image_set ) break;
        }
      }
      
    }
    
    return $image;
    
  }
  
  
  
}