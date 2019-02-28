<?php
namespace IM;

class IntervalScope
{
    private $intervals = [];

    /**
     * @return array
     */
    public function getIntervals()
    {
        return $this->intervals;
    }

    /**
     * @param [][$beforeInteger, $afterInteger] $intervals
     */
    public function setIntervals($intervals)
    {
        $this->intervals = $intervals;
    }

    /**
     * TODO needs have unit test
     *
     * Вставляет интервал и копирует его в каждый из $numberOfCopiesBefore дней "до" и $numberOfCopiesAfter дней "после".
     * При этом уже имеющиеся интервалы дополняются
     *
     * @param $beginTime int
     * @param $endTime int
     * @param $numberOfCopiesBefore int
     * @param $numberOfCopiesAfter int
     * @throws Exception
     */
    public function insertAndDuplicateIntervals($beginTime, $endTime, $numberOfCopiesBefore, $numberOfCopiesAfter)
    {
        $startRange = $this->checkAndMoveIntervals($beginTime, $endTime);

        $secondsInDay = 24*60*60;

        if($numberOfCopiesBefore){
            for($i = $numberOfCopiesBefore; $i>0; $i--){
                $resultRanges[] = [
                    $startRange[0] - $secondsInDay * $i,
                    $startRange[1] - $secondsInDay * $i
                ];
            }
        }

        $resultRanges[] = $startRange;

        if($numberOfCopiesAfter){
            for($i = 1; $i < $numberOfCopiesAfter; $i++){
                $resultRanges[] = [
                    $startRange[0] + $secondsInDay * $i,
                    $startRange[1] + $secondsInDay * $i
                ];
            }
        }

        $j = array_merge($this->intervals, $resultRanges);
        $this->intervals = IntervalMath::mergeIntersectedIntervals($j);
    }

    /**
     * @param $begin int
     * @param $end int
     */
    public function insertInterval($begin, $end)
    {
        $this->intervals = IntervalMath::mergeIntersectedIntervals(
            array_merge(
                $this->intervals,
                $this->checkAndMoveIntervals($begin, $end)
        ));
    }

    public function mergeIntersected()
    {
        $this->intervals = IntervalMath::mergeIntersectedIntervals($this->intervals);
    }

    public function orWith(IntervalScope $scope)
    {
        $this->intervals = IntervalMath::bool($this->intervals, $scope->intervals, IntervalMath::A_OR_B);
    }

    public function andWith(IntervalScope $scope)
    {
        $this->intervals = IntervalMath::bool($this->intervals, $scope->intervals, IntervalMath::A_AND_B);
    }

    /**
     * TODO WFT??
     *
     * @param IntervalScope $scope
     */
    public function minus(IntervalScope $scope)
    {
        $this->intervals = IntervalMath::bool($this->intervals, $scope->intervals, IntervalMath::A_AND_B);
    }

    /**
     * @param $begin
     * @param $end
     * @return array
     * @throws Exception
     */
    private function checkAndMoveIntervals($begin, $end)
    {
        if(empty($begin) || empty($end) || $begin == $end){
            throw new \Exception("Invalid begin or end time, or they equals");
        }

        if ($begin > $end) {
            $t = $end;
            $end = $begin;
            $begin = $t;
        }

        return [$begin, $end];
    }
}