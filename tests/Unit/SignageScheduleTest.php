<?php
namespace Tests\Unit;
use App\Services\Signage\SignageScheduleResolver;
class SignageScheduleTest extends \PHPUnit\Framework\TestCase {
    private function rule(array $values=[]): array {return $values+['release_id'=>2,'days'=>[1],'starts_on'=>'2026-01-01','ends_on'=>'2026-12-31','start_time'=>'22:00','end_time'=>'02:00','priority'=>1];}
    public function test_overnight_window_belongs_to_starting_day_and_highest_priority_wins(): void {
        $service=new SignageScheduleResolver();$rules=[$this->rule(),$this->rule(['release_id'=>3,'priority'=>2])];
        $this->assertSame(3,$service->select($rules,1,new \DateTimeImmutable('2026-11-10T00:30:00+01:00')));
        $this->assertSame(1,$service->select($rules,1,new \DateTimeImmutable('2026-11-10T02:00:00+01:00')));
    }
    public function test_dst_and_equal_priority_overlap_are_explicit(): void {
        $service=new SignageScheduleResolver();$rule=$this->rule(['days'=>[7],'start_time'=>'01:00','end_time'=>'04:00']);
        foreach(['2026-10-25T00:30:00Z','2026-10-25T01:30:00Z'] as $time)$this->assertSame(2,$service->select([$rule],1,new \DateTimeImmutable($time)));
        $this->assertTrue($service->overlaps($rule,$rule));
        $this->assertFalse($service->overlaps($rule,$this->rule(['days'=>[2]])));
    }
}
