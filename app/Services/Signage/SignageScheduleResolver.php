<?php
namespace App\Services\Signage;
use DateTimeImmutable;
use DateTimeZone;
class SignageScheduleResolver {
    public function select(array $rules,?int $default,DateTimeImmutable $now): ?int {
        usort($rules,fn($a,$b)=>$b['priority']<=>$a['priority']);
        $local=$now->setTimezone(new DateTimeZone('Europe/Budapest'));
        foreach($rules as $rule){
            foreach([$local,$local->modify('-1 day')] as $date){
                [$start,$end]=$this->window($rule,$date);
                if($this->validDay($rule,$date)&&$local>=$start&&$local<$end)return (int)$rule['release_id'];
            }
        }
        return $default;
    }
    private function validDay(array $rule,DateTimeImmutable $date): bool {
        return $date->format('Y-m-d')>=$rule['starts_on']&&$date->format('Y-m-d')<=$rule['ends_on']
            &&in_array((int)$date->format('N'),array_map('intval',$rule['days']),true);
    }
    private function window(array $rule,DateTimeImmutable $date): array {
        $start=new DateTimeImmutable($date->format('Y-m-d').' '.$rule['start_time'],new DateTimeZone('Europe/Budapest'));
        $end=new DateTimeImmutable($date->format('Y-m-d').' '.$rule['end_time'],new DateTimeZone('Europe/Budapest'));
        if($end<=$start)$end=$end->modify('+1 day');
        return [$start,$end];
    }
    public function overlaps(array $a,array $b): bool {
        if((int)$a['priority']!==(int)$b['priority'])return false;
        $from=new DateTimeImmutable(max($a['starts_on'],$b['starts_on']),new DateTimeZone('Europe/Budapest'));
        $until=new DateTimeImmutable(min($a['ends_on'],$b['ends_on']),new DateTimeZone('Europe/Budapest'));
        // Weekly patterns repeat; eight days plus adjacent overnight windows cover their overlap.
        for($date=$from->modify('-1 day');$date<=$until&&$date<=$from->modify('+8 days');$date=$date->modify('+1 day')){
            if(!$this->validDay($a,$date))continue;
            [$as,$ae]=$this->window($a,$date);
            foreach([$date->modify('-1 day'),$date,$date->modify('+1 day')] as $other){
                if(!$this->validDay($b,$other))continue;
                [$bs,$be]=$this->window($b,$other);
                if($as<$be&&$bs<$ae)return true;
            }
        }
        return false;
    }
}
