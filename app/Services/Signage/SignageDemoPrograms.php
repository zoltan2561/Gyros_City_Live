<?php

namespace App\Services\Signage;

use DateTimeImmutable;

/** Video recreations, a general advert and three original modern versions. */
class SignageDemoPrograms
{
    public function definitions(): array
    {
        $sequence=0;
        $row=function(int $id, string $title='', string $addons='', string $art='menu') use (&$sequence): array {
            return ['id'=>'ref-'.(++$sequence),'item_id'=>$id,'title'=>$title,'addons_id'=>$addons,'artwork'=>$art.'-'.$id];
        };
        $burgerRows=[];
        foreach([60=>'City Burger',61=>'City Burger Plusz',62=>'City Burger Maxi',63=>'Jasper Burger',65=>'Fish Burger',64=>'Chicken Burger'] as $id=>$name) $burgerRows[]=$row($id,$name,'','burger');
        $scene=fn($id,$design,$rows,$duration,$background='#525252')=>[
            'id'=>$id,'design'=>$design,'type'=>'grid','theme'=>$background==='#ffffff'?'light':'dark',
            'title'=>'','background'=>$background,'duration'=>$duration,'bindings'=>$rows,
        ];
        $burger=$scene('reference-burgers','reference-burger',$burgerRows,20,'#170000');
        $menus=[
            $scene('menu-burgers','reference-menu',array_map(fn($b)=>$row($b['item_id'],$b['title'],'','menu'),$burgerRows),20,'#ffffff'),
            $scene('menu-bundles','reference-menu',[
                $row(135,'City Burger menü','1270|116'),$row(136,'City Burger Plusz menü','1270|116'),
                $row(137,'City Burger Maxi menü','1267|116'),$row(140,'Fishburger menü','1270|116'),
                $row(139,'Chickenburger menü','1270|116'),$row(138,'Jasper Burger menü','1270|116'),
                $row(134,'City Burger páros ajánlat','1270|1238|1271|1272'),
            ],20,'#ffffff'),
            $scene('menu-gyros','reference-menu',[
                $row(128,'Gyros-tál / sajttal','2|1'),$row(133,'Gyros-tál extra sajttal','2|56'),
                $row(156,'Vega-tál','2|1'),$row(66,'Hot-dog sima'),
                $row(76,'Gyros kifli','2'),$row(77,'Gyros pita','2'),$row(78,'Gyros tortilla','2'),$row(153,'Melegszendvics kifli'),
            ],20),
            $scene('menu-fried','reference-menu',[
                $row(161,'Rántott szelet (2 db) + köret','1'),$row(167,'Rántott szelet menü','1|1270'),
                $row(158,'Rántott sajt (2 db) + köret','1'),$row(165,'Rántott sajt menü','1|1270|1282'),
                $row(160,'Panírozott halrudacska + köret','1'),$row(159,'Natúr csirkemell sajtosan + köret','5'),
                $row(282,'Nuggets falatok + köret','1'),$row(248,'Nuggets menü','1|1270|1282'),
            ],20),
            $scene('menu-sides','reference-menu',[
                $row(95,'Csirkemell saláta'),$row(93,'Görög saláta'),$row(169,'Babgulyás'),
                $row(170,'Sültburgonya'),$row(174,'Gesztenyepüré'),$row(173,'Palacsinta','27'),
            ],8),
            $scene('menu-kebab','reference-kebab',[
                $row(121,'Kebab-tál','1|11|1404'),$row(124,'Csirke Dürüm menü','11|1404|1270|116'),
                $row(119,'Csirke Döner','11|1404'),$row(120,'Csirke Dürüm','11|1404'),$row(123,'Csirke Döner menü','11|1404|1270|116'),
            ],8),
        ];
        // The second dynamic price belongs to the same gyro card.
        $cheese=$row(129,'Gyros-tál / sajttal','2|1');unset($cheese['artwork']);
        $menus[2]['bindings'][0]['card_id']='gyro-plate';$cheese['card_id']='gyro-plate';
        $menus[2]['bindings'][0]['group']='Gyros-tál / sajttal';$cheese['group']='Gyros-tál / sajttal';
        array_splice($menus[2]['bindings'],1,0,[$cheese]);
        $pizzaRows=[];
        foreach([336=>"'Nduja pizza",342=>'Bufala pizza',341=>'Carbonara pizza',339=>'Húsimádó',333=>'Diavola pizza',335=>'Görögös',334=>'Mexikói',332=>'Mimóza',338=>'Magyaros',329=>'Songoku'] as $id=>$name) $pizzaRows[]=$row($id,$name,'','pizza');
        $pizza=$scene('reference-pizza','reference-pizza',$pizzaRows,200,'#170000');
        $pizza['type']='slideshow';$pizza['slide_seconds']=20;
        $intro=$scene('reference-pizza-intro','reference-intro',[$row(342,'Pizza Time!','','pizza')],40,'#080808');
        $intro['type']='featured';
        $general=$scene('general-advert','standard',[
            $row(60,'City Burger','','burger'),$row(336,"'Nduja pizza",'','pizza'),
            $row(342,'Bufala pizza','','pizza'),$row(121,'Kebab-tál','1|11|1404'),
            $row(120,'Csirke Dürüm','11|1404'),$row(173,'Palacsinta','27'),
        ],42);
        $general=array_replace($general,app(SignageBackgrounds::class)->options()['graphite']);
        unset($general['label']);
        $general['title']='A kedvenceid, frissen.';
        $general['type']='slideshow';$general['slide_seconds']=7;$general['backdrop']='graphite';
        $modern=function(array $source,string $design,string $title,array $colors)use(&$sequence):array{
            $source['id']='modern-'.str_replace('reference-','',$source['id']);
            $source['design']=$design;$source['title']=$title;
            foreach($source['bindings'] as &$binding)$binding['id']='ref-'.(++$sequence);
            unset($binding);
            return array_replace($source,$colors);
        };
        $modernBurger=$modern($burger,'modern-burger','Burgerek. City módra.',[
            'theme'=>'light','background'=>'#f3be3f','accent'=>'#26190e','text_color'=>'#26190e',
        ]);
        foreach($modernBurger['bindings'] as &$binding)$binding['artwork']='menu-'.$binding['item_id'];
        unset($binding);
        $modernBurger['type']='slideshow';$modernBurger['slide_seconds']=8;$modernBurger['duration']=48;
        $modernMenus=[];
        foreach(['Burgerek','Burger menük','Gyrosok és kedvencek','Frissensültek','Köretek és desszertek','Kebab, döner, dürüm'] as $i=>$title){
            $modernMenus[]=$modern($menus[$i],'modern-menu',$title,[
                'theme'=>'light','background'=>'#f1f4f0','accent'=>'#155345','text_color'=>'#132b24',
                'duration'=>[20,22,24,24,14,18][$i],
            ]);
        }
        foreach($modernMenus[2]['bindings'] as &$binding)if(in_array($binding['item_id'],[128,129],true)){
            $binding['title']='Gyros-tál';
            $binding['label']=$binding['item_id']===129?'Sajttal':'';
        }
        unset($binding);
        $modernPizza=$modern($pizza,'modern-pizza','Pizzák, ahogy szeretjük.',[
            'theme'=>'dark','background'=>'#153b30','accent'=>'#e5b662','text_color'=>'#fcf9f0',
        ]);
        $modernPizza['slide_seconds']=10;$modernPizza['duration']=100;
        return [
            ['id'=>'burger','name'=>'Burger · az eredeti videó alapján','scenes'=>[$burger]],
            ['id'=>'menu','name'=>'Étlap · az eredeti videó alapján','scenes'=>$menus],
            ['id'=>'pizza','name'=>'Pizza · az eredeti videó alapján','scenes'=>[$intro,$pizza]],
            ['id'=>'general','name'=>'Általános reklám','scenes'=>[$general]],
            ['id'=>'modern-burger','name'=>'Burger · saját modern változat','scenes'=>[$modernBurger]],
            ['id'=>'modern-menu','name'=>'Étlap · saját modern változat','scenes'=>$modernMenus],
            ['id'=>'modern-pizza','name'=>'Pizza · saját modern változat','scenes'=>[$modernPizza]],
        ];
    }

    public function build(DateTimeImmutable $now): array
    {
        $programs=$this->definitions();$bindings=[];
        foreach($programs as $program)foreach($program['scenes'] as $scene)foreach($scene['bindings'] as $binding)$bindings[$binding['id']]=$binding;
        $catalog=app(SignageCatalog::class)->snapshot(array_values($bindings),$now);
        $available=array_column(array_filter($catalog['products'],fn($p)=>$p['available']&&$p['image']&&(float)$p['price']>0),'id');
        foreach($programs as &$program){
            foreach($program['scenes'] as &$scene){
                $scene['bindings']=array_values(array_filter($scene['bindings'],fn($b)=>in_array($b['id'],$available,true)));
                if($scene['type']==='slideshow')$scene['duration']=count($scene['bindings'])*$scene['slide_seconds'];
                $scene=app(SignageReferenceAssets::class)->decorate($scene);
            }unset($scene);
            $program['scenes']=array_values(array_filter($program['scenes'],fn($s)=>count($s['bindings'])));
        }unset($program);
        return $catalog+['programs'=>$programs];
    }
}
