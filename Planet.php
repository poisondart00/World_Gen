<?php
class Planet{
	public $version = '6.6'; // Version Number
	public $log = '<fieldset><legend>November 23, 2017<span class=minimize></span></legend>Minor change to the spherize function making edges less crisp.<br>Updated rings and moons to work with the change.</fieldset>
	<fieldset><legend>July 24, 2017<span class=minimize></span></legend>Made a few adjustments to server side code to allow for more custom options.</fieldset>
<fieldset><legend>July 8, 2017<span class=minimize></span></legend>Updated graphics rendering for land, water, and clouds.</fieldset>';
	public $size = 400; // Beginning Size Of Noise Image.
	public $seed = null; // Seed Of Planet.
	public $noise = null; // Noise Image Path.
	public $image = null; // Finalized Image Path.
	public $height_map = null; // Path To Height Map.
	public $water_land_divizor = 18; // Amount Of Water And Land Adjustment, Higher Number More Water, 18 = Just Right.
	public $land_image = null; // Land Image Path.
	public $water_image = null; // Water Image Path.
	public $land_color_array = null; // Land Colors Sent To Populate Image.
	public $water_color_array = null; // Water Colors Sent To Populate Image.
	public $reflection = 0; // If Water Present Make Star Light Reflection.
	public $reflection_intensity = 0.9; // Amount Of Star Reflection. 0.9 = Just Right For Earth Like Planets
	public $clouds = 0; // If Clouds Are Included In Image.
	public $cloud_image = null; // Cloud Image Path.
	public $cloud_coverage = 7; // Amount Of Clouds In Image, Lower Number More Clouds, 12 = Just Right
	public $sharpen_image = 4; // Amount Of Sharpening Loop For Difference In Noise Colors.
	public $gas = 0; // Determine If Image Will Be For A Gas Giant.
	public $emboss = 0; // Emboss Image For Greater Detail in Gas Planets.
	public $blur = 80; // Amount Of Motion Blur Applied To Gas Giants. After 99 Motion Blur Is Converted Into Intense Lines (Like Jupiter)
	public $swirl_count = 35; // Amount Of Random Swirls Applied.
	public $swirl_intensity = 400; // Intensity Of Swirls.
	public $rotation = 0; // Rotation Of Planet.
	public $resize = null; // Resized Image (Note: Original Image Will Already Be Reduced By 50% By The Sphereize Function).
	public $rings = null; // Array Of Planet Ring Data Example: $img->rings = array(array('ring_width' => 4, 'ring_space' => 0, 'ring_color' => 'grey', 'ring_opacity' => 1));.
	public $moons = null; // Array Of Planet Moon Data Example: $img->moons = array(array('moon_size' = 5, 'moon_land_color' => array('red','green'), 'moon_water_color' => array('red','green'), 'moon_distance' => 5));

	public function Generate_Moons(){
		$full_moon = new Imagick();
		$full_moon->newImage($this->size, $this->size, 'none');
		for($i=0; $i<=count($this->moons)-1; $i++){
			$moon = new Planet;
			$moon->noise = $this->noise;
			$moon->land_color_array = $this->moons[$i]['land_color'];
			if($this->moons[$i]['water_color']){
				$moon->water_color_array = $this->moons[$i]['water_color'];
			}
			if($this->moons[$i]['clouds']){
				$moon->clouds = $this->moons[$i]['clouds'];
			}
			if($this->moons[$i]['swirl_count']){
				$moon->clouds = $this->moons[$i]['swirl_count'];
			}
			if($this->moons[$i]['swirl_intensity']){
				$moon->clouds = $this->moons[$i]['swirl_intensity'];
			}
			if($this->moons[$i]['blur']){
				$moon->blur = $this->moons[$i]['blur'];
			}
			if($this->moons[$i]['gas']){
				$moon->gas = $this->moons[$i]['gas'];
			}
			if($this->moons[$i]['emboss']){
				$moon->emboss = $this->moons[$i]['emboss'];
			}
			if($this->moons[$i]['reflection']){
				$moon->reflection = 1;
				$moon->reflection_intensity = $this->moons[$i]['reflection'];
			}
			if($this->moons[$i]['rings']){
				$moon->rings = $this->moons[$i]['rings'];
			}
			if($this->resize){
				$moon->resize = (($this->moons[$i]['size']/100)*$this->resize);
			} else {
				$moon->resize = (($this->moons[$i]['size']/100)*$this->size);
			}
			$moon->sharpen_image = 12;
			$moon->init();
			$wh=$full_moon->getImageGeometry();

			$rotate = ($this->moons[$i]['rotate'])? $this->moons[$i]['rotate']: mt_rand(0,360);
			$radius = (($wh['width']-$this->moons[$i]['spacing'])/2)+($moon->resize/2);
			$rotate = ($this->moons[$i]['rotate'] <= 1)? 1: $this->moons[$i]['rotate'];
			
			$x = floor(($wh['width']/2) + (($radius-($radius*0.2)) * cos(deg2rad($rotate))));
			$y = floor(($wh['width']/2) + ((($radius-($radius*0.2))/3) * sin(deg2rad($rotate))));
			$full_moon->compositeImage($moon->image, imagick::COMPOSITE_OVER, $x-($moon->resize/2), $y-($moon->resize/2));
			
		}
		$wh=$this->image->getImageGeometry();
		$im = clone $this->image;
		$this->image = new Imagick();
		$this->image->newImage($this->size, $this->size, 'none');
		$this->image->compositeImage($im, imagick::COMPOSITE_DISSOLVE, (($this->size/2)-($wh['width']/2)), (($this->size/2)-($wh['width']/2)));
		$im->destroy();
		$this->image = $this->Planet_Half_Mask($full_moon, $this->image);
	}


	
	public function Generate_Rings(){
		$ring = new Imagick();
		$ring->newImage($this->size, $this->size, 'none');
		$draw = new ImagickDraw();
		$draw->setFillColor('transparent');
		$draw->setStrokeWidth(1); 
		for($i=0; $i<=count($this->rings)-1; $i++){
			if(!$this->rings[$i]['ring_color']){
				$draw->setStrokeColor('grey');
			} else {
				$draw->setStrokeColor($this->rings[$i]['ring_color']);
			}
			if(!$this->rings[$i]['ring_opacity']){
				$draw->setStrokeOpacity(0.4);
			} else {
				$draw->setStrokeOpacity($this->rings[$i]['ring_opacity']);
			}
			$this->rings[$i]['ring_width'] = ($this->rings[$i]['ring_width'])? $this->rings[$i]['ring_width']: 5;
			for($j=$this->rings[$i]['ring_space']; $j<=($this->rings[$i]['ring_width']+$this->rings[$i]['ring_space']); $j++){
				$draw->arc((3+($j/2)*2.7), ((($this->size/2.7)+($j/2))-4), (($this->size-3)-(($j/2)*2.7)), ((($this->size-($this->size/2.7))-($j/2))-4), 0, 360);
			}
		}
		$ring->drawImage($draw);
		$wh=$this->image->getImageGeometry();
		$im = clone $this->image;
		$this->image = new Imagick();
		$this->image->newImage($this->size, $this->size, 'none');
		$this->image->compositeImage($im, imagick::COMPOSITE_DISSOLVE, (($this->size/2)-($wh['width']/2)), (($this->size/2)-($wh['width']/2)));
		$im->destroy();
		$this->image = $this->Planet_Half_Mask($ring, $this->image);
	}
	
	public function Planet_Half_Mask($masked, $adding){
		$mask = new Imagick();
		$mask->newImage($this->size, $this->size, 'transparent');
		$draw = new ImagickDraw();
		$draw->setStrokeColor('none');
		$draw->setFillColor('white');
		$draw->arc(($this->size/2+3)/2, ($this->size/2)/2, $this->size/2+(($this->size/2-3)/2), $this->size/2+(($this->size/2)/2), 175, 365);
		$mask->drawImage($draw);
		//$masked->compositeImage($mask, Imagick::COMPOSITE_OVER, 0, -15);
		$masked->compositeImage($mask, Imagick::COMPOSITE_DSTOUT, 0, -15);
		$mask->destroy();
		$adding->compositeImage($masked, imagick::COMPOSITE_DISSOLVE, 0, 15);
		$masked->destroy();
		return $adding;
	}

	public function Shade_Image(){
		$wh = $this->image->getImageGeometry();
		$shade = new Imagick();
		$shade->newPseudoImage($wh['width']*1.5, $wh['width']*1.5, 'radial-gradient:transparent-black');
		$this->image->compositeImage($shade, imagick::COMPOSITE_DISSOLVE, 0, 0);
		$shade->destroy(); 
		$circle_mask = new Imagick();
		$circle_mask->newImage($wh['width'], $wh['width'], new ImagickPixel('transparent'));
		$mask = new ImagickDraw();
		$mask->setfillcolor('black');
		$mask->circle($wh['width']/2, $wh['width']/2, $wh['width']/2, $wh['width']-1);
		$circle_mask->drawimage($mask);
		$mask->destroy();
		$this->image->compositeImage($circle_mask, Imagick::COMPOSITE_COPYOPACITY, 0, 0);
		$circle_mask->destroy();
	} 
	
	public Function Init(){
		if(!$this->seed){
			$this->seed = time();
		}
		mt_srand($this->seed);
		if(!$this->noise){
			$this->Generate_Noise();
		}
		
		if($this->gas){
			$this->Gas_Planet($this->Create_Gradient($this->land_color_array));
		} else {
			if($this->land_color_array && $this->water_color_array){
				$this->Height_Map();
			} else {
				$this->land_image = clone $this->noise;
			}
			if($this->land_color_array){
				$this->land_image = $this->Colorize_image($this->land_image, $this->Create_Gradient($this->land_color_array), $this->sharpen_image, 0, 1);
			}
			if($this->water_color_array){
				$this->water_image = $this->Colorize_image($this->water_image, $this->Create_Gradient($this->water_color_array), 0, 1);
			}
			if($this->clouds){
				$this->Generate_clouds();
			}
		}
		if($this->reflection){
			if(!$this->water_image){
				$this->land_image = $this->Water_Reflection($this->land_image);
			} else {
				$this->water_image = $this->Water_Reflection($this->water_image);
			}
		}
		$this->Flaten_Image();
		
		$this->Sphereize_Image();
		$this->Shade_Image();
		if($this->rings){
			$this->Generate_Rings();
		}
		if($this->moons){
			$this->Generate_Moons();
		}
		if($this->resize){
			$this->Resize_Image();
		}
		
		
		$this->image->setImageFormat("png");
		$this->image->getImageBlob();

	}
	
	
	public function Gas_Planet($gradient_array)
	{
		$this->land_image = clone $this->noise;
		if($this->blur >= 100){
			$blur = clone $this->land_image;
			$blur->setImageOpacity(.1);
			for($i=-$this->size/2; $i<=$this->size; $i+=1){
				$this->land_image->compositeImage($blur, Imagick::COMPOSITE_OVER, $i, 0);
			}
			$blur->destroy();
		} else {
			$this->land_image->motionBlurImage($this->blur, $this->blur, 0);
		}
   		
   		for($i=0; $i<=$this->sharpen_image; $i++){
   			$this->land_image->contrastImage(1);
   		}
   		for($i=0; $i<$this->swirl_count; $i++){
			$swirl = clone $this->land_image;
			$swirl_size = mt_rand(($this->size/12),($this->size/6));
			$x = mt_rand(0,$this->size-$swirl_size);
			$y = mt_rand(0,$this->size-$swirl_size);
			$swirl->cropImage($swirl_size,$swirl_size,$x,$y);
			$swirl->swirlImage(mt_rand($this->swirl_intensity,-$this->swirl_intensity));
			$this->land_image->compositeImage($swirl, Imagick::COMPOSITE_DISSOLVE, $x, $y);
			$swirl->destroy();
		}
		if($this->emboss){
   			$this->land_image->embossImage(1,20);
   			$this->land_image->sharpenImage(1,20);
   		}
   		
		for($q=0; $q<=255; $q++){
			$this->land_image->paintOpaqueImage('rgb('.$q.','.$q.','.$q.')', $gradient_array[$q], 200);
		}
		
	}

	public function Sphereize_Image(){
		$g = new Imagick();
		$g->newPseudoImage($this->size/2, $this->size/2, 'radial-gradient:black-white');
		$m = clone $g;
		$m->negateImage(1);
		$m->setImageBackgroundColor('black');
		$m->extentImage($this->size,$this->size,-$this->size/4,-$this->size/4);
		$m->thresholdimage(0);
		$g->functionImage(Imagick::FUNCTION_POLYNOMIAL, array(.9,-1.2,.8,.4));
		for($i=0; $i<=10; $i++){
			$g->contrastImage(1);
			$g->contrastImage(0);
		}
		$g1 = new Imagick();
		$g1->newPseudoImage($this->size/2, $this->size/2, 'gradient:black-white');
		$g1->addImage($g);
		$g = $g1->fxImage("0.5*((2*u-1)*v)+0.5");
		$g1->destroy();
		$this->image->compositeImage($g, imagick::COMPOSITE_DISPLACE, $this->size/4, $this->size/4);
		$g->destroy();
		$this->image->compositeImage($m, Imagick::COMPOSITE_COPYOPACITY, 0,0);
		$m->destroy();
		
		$mask = new Imagick();
		$mask->newPseudoImage($this->size/1.99, $this->size/1.99, 'radial-gradient:white-transparent');
		$mask->evaluateImage(Imagick::EVALUATE_MULTIPLY, 12, Imagick::CHANNEL_ALPHA);
		$this->image->compositeImage($mask, imagick::COMPOSITE_COPYOPACITY, ($this->size/4+($this->size*.02)), ($this->size/4+($this->size*.02)));
		$mask->destroy();
		
		$this->image->cropImage($this->size/2, $this->size/2, $this->size/4, $this->size/4);
		$who=$this->image->getImageGeometry();
		$this->image->rotateImage("none", $this->rotation);
		$wh=$this->image->getImageGeometry();
		if($wh['width'] > $who['width']){
			$center = ($wh['width']/2);
			$this->image->cropImage($who['width']+2, $who['width']+2, -1, -1);
		}
	}
	
	public Function Generate_Clouds(){
	   	$this->cloud_image = clone $this->noise;
	   	$this->cloud_image->rotateImage('none', 270);
	   	for($i=0; $i<=22; $i++){
   			$this->cloud_image->contrastImage(1); // Seperate Black From White
   		}
   		$this->cloud_image->embossImage(1,2);
   		$this->cloud_image->solarizeImage(1500);
		$this->cloud_image->paintTransparentImage('rgb(0,0,0)', 0, 20000);
		$this->cloud_image->modulateImage(125,100,100);
		for($i=0; $i<$this->swirl_count; $i++){
			$swirl = clone $this->cloud_image;
			$swirl_size = mt_rand(($this->size/6),($this->size/3));
			$x = mt_rand(0,$this->size-$swirl_size);
			$y = mt_rand(0,$this->size-$swirl_size);
			$swirl->cropImage($swirl_size,$swirl_size,$x,$y);
			$swirl->swirlImage(mt_rand($this->swirl_intensity,-$this->swirl_intensity));
			$this->cloud_image->compositeImage($swirl, Imagick::COMPOSITE_DISSOLVE, $x, $y);
			$swirl->destroy();
		}
		$this->cloud_image->paintTransparentImage('rgb(255,255,255)', 0, $this->cloud_coverage*1000);
		$this->cloud_image->sharpenimage(3,7);
		$this->cloud_image->convolveImage(array(-1,-1,0,-1,1,1,0,1,1));
		$this->cloud_image->modulateImage(125,100,100);
	}
	
	public function Water_Reflection($image)
	{
		if(!$this->height_map){
			$this->height_map = new Imagick();
			$this->height_map->newImage($this->size, $this->size, 'none');
		}
   		$r2 = new Imagick();
		$r2->newPseudoImage($this->size*.4, $this->size*.4, 'radial-gradient:white-transparent');
		$r2->compositeImage($this->height_map, imagick::COMPOSITE_DSTOUT, -$this->size*.5, -$this->size*.5, Imagick::CHANNEL_ALPHA);
		$r2->evaluateImage(Imagick::EVALUATE_MULTIPLY, $this->reflection_intensity, Imagick::CHANNEL_ALPHA);
		$image->compositeImage($r2, imagick::COMPOSITE_OVER, $this->size*.5, $this->size*.5);
		$r2->destroy();
		$this->height_map->destroy();
		return $image;
	}
	
	public function Colorize_Image($image, $gradient_array, $sharpen, $blur = 0, $convolve = 0)
	{
   		for($i=1; $i<=$sharpen; $i++){
   			$image->contrastImage(1);
   		}
   		if($blur){
   			for($i=1; $i<=$blur; $i++){
   				$image->blurImage(0,1);
   			}
   		}
   		$image->sharpenImage(1,20);
   		$image->embossImage(1,20);
		for($q=0; $q<=255; $q++){
			$image->paintOpaqueImage('rgb('.$q.','.$q.','.$q.')', $gradient_array[$q], 400);
		}
		if($convolve){
			$image->convolveImage(array(-1,-2,-13,1,-15,-1,13,2,1));
		}
		return $image;
	}
	
	public function Create_Gradient($color_array){ // Create Gradient Image And Color Array
   		$gradient_image = new Imagick();
  		$gradient_image->newImage(255,1,'none');
  		$color_array = array_reverse($color_array);
		for($i=0; $i<=count($color_array)-1; $i++){ // Creates Gradient Image
			$z = new Imagick();
			$z->newPseudoImage(round(255/(count($color_array)-1)), 1, 'gradient:'.$color_array[$i].'-'.$color_array[($i+1)]);
			$gradient_image->compositeImage($z, Imagick::COMPOSITE_OVER, (($i)*round(255/(count($color_array)-1))), 0);
			$z->destroy(); 
		}
		for($q=0; $q<=255; $q++){ // Generates Gradient Array
			$gradient_array[$q] = $gradient_image->getImagePixelColor($q, 0); 
			$gradient_array[$q] = $gradient_array[$q]->getColorAsString();
		}
		$gradient_image->destroy();
		return $gradient_array;
   	}
	
	public function Height_Map(){ // Generate 2 Height Maps From Noise Image
   		$this->height_map = clone $this->noise;
   		for($i=1; $i<=$this->water_land_divizor; $i++){
   			$this->height_map->modulateImage(101,100,100);
   			$this->height_map->contrastImage(1); // Seperate Black From White
   		}
   		$this->height_map->paintTransparentImage('rgb(255,255,255)', 0, 255);
   		$this->water_image = clone $this->noise;
   		$this->water_image->compositeImage($this->height_map, imagick::COMPOSITE_DSTOUT, 0, 0, Imagick::CHANNEL_ALPHA);
   		$this->land_image = clone $this->noise;
   		$this->land_image->compositeImage($this->height_map, imagick::COMPOSITE_DSTIN, 0, 0, Imagick::CHANNEL_ALPHA);
   	}
   
	
	public Function Generate_Noise(){ // Generate Initial Noise Image
		mt_srand($this->seed);
		$this->noise = new Imagick();
		$this->noise->newImage(400, 400, 'black');
		$d = new ImagickDraw();
		$d->setfillcolor('white');
		$d->setFillOpacity('.001');
		for($i=0; $i<=5000; $i++){
			$s_x = mt_rand(-100,(500));
			$s_y = mt_rand(-100,(500));
			//$d->rectangle($s_x, $s_y, ($s_x+mt_rand(-25,25)), ($s_y+mt_rand(-25,25)));
			$d->circle($s_x, $s_y, ($s_x+mt_rand(-25,25)), ($s_y+mt_rand(-25,25)));
		}
		$this->noise->drawimage($d);
		$d->destroy();
		$this->noise->modulateImage(2900, 0, 100);

   	}
   	
	public function Flaten_Image(){
		$this->image = new Imagick();
		$this->image->newImage($this->size, $this->size, 'none');
		if($this->water_image){ 
			$this->image->compositeImage($this->water_image, Imagick::COMPOSITE_OVER, 0, 0);
		}
		if($this->land_image){ 
			$this->image->compositeImage($this->land_image, Imagick::COMPOSITE_OVER, 0, 0);
		}
		if($this->cloud_image){ 
			$this->image->compositeImage($this->cloud_image, Imagick::COMPOSITE_OVER, 0, 0);
		}
   	}
   	
   	public Function Resize_Image(){
		$this->image->adaptiveResizeImage($this->resize,$this->resize);
   	}

}
?>
