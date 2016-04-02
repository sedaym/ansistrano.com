<?php

namespace Ansistrano;

class DeploymentsCounter
{
    const DEPLOYMENTS = 'deployments';
    const ROLLBACKS = 'rollbacks';

    const TOTAL = 'ansistrano:%s:total';
    const TOTAL_BY_MONTH = 'ansistrano:%s:total:month:%s';
    const TOTAL_BY_MONTH_AND_DAY = 'ansistrano:%s:total:month:%s:day:%s';
    const TOTAL_BY_MONT_DAY_HOUR = 'ansistrano:%s:total:month:%s:day:%s:hour:%s';
    const TOTAL_BY_YEAR = 'ansistrano:%s:total:year:%s';
    const TOTAL_BY_YEAR_AND_MONTH = 'ansistrano:%s:total:year:%s:month:%s';
    const TOTAL_BY_DATE = 'ansistrano:%s:total:year:%s:month:%s:day:%s';
    const TOTAL_BY_DATE_AND_HOUR = 'ansistrano:%s:total:year:%s:month:%s:day:%s:hour:%s';
    const TOTAL_BY_WEEKDAY = 'ansistrano:%s:total:weekday:%s';
    const TOTAL_BY_WEEKDAY_AND_HOUR = 'ansistrano:%s:total:weekday:%s:hour:%s';

    /**
     * @var StatsRepository
     */
    private $statsRepository;

    public function __construct(StatsRepository $statsRepository)
    {
        $this->statsRepository = $statsRepository;
    }

    public function addDeployment(\DateTimeImmutable $date)
    {
        $this->addOperation($date, self::DEPLOYMENTS);
    }

    public function addRollback(\DateTimeImmutable $date)
    {
        $this->addOperation($date, self::ROLLBACKS);
    }

    private function addOperation(\DateTimeImmutable $date, $operation)
    {
        list($year, $month, $day, $dayOfWeek, $hour) = $this->splitDate($date);

        $this->statsRepository->increment(sprintf(self::TOTAL,                     $operation));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_MONTH,            $operation, $month));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_MONTH_AND_DAY,    $operation, $month, $day));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_MONT_DAY_HOUR,    $operation, $month, $day, $hour));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_YEAR,             $operation, $year));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_YEAR_AND_MONTH,   $operation, $year, $month));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_DATE,             $operation, $year, $month, $day));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_DATE_AND_HOUR,    $operation, $year, $month, $day, $hour));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_WEEKDAY,          $operation, $dayOfWeek));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_WEEKDAY_AND_HOUR, $operation, $dayOfWeek, $hour));
    }

    public function statsFor(\DateTimeImmutable $date)
    {
        list($year, $month, $day, $dayOfWeek, $hour) = $this->splitDate($date);

        $stats = [];
        foreach(['deployments', 'rollbacks'] as $operation) {
            $statsByWeekDayAndHour = [];
            $statsByWeekDayAndHourMax = 0;

            $statsByWeekDay = new \stdClass();
            $statsByWeekDay->percentage = [];
            $statsByWeekDay->days = [];
            $statsByWeekDay->total = 0;

            foreach (range(0, 6) as $weekDay) {
                $statsByWeekDay->days[$weekDay] = 0;
                foreach (range(0, 23) as $hourDay) {
                    $statsByWeekDayAndHour[$weekDay][$hourDay] = $this->statsRepository->get(sprintf(self::TOTAL_BY_WEEKDAY_AND_HOUR, $operation, $weekDay, $hourDay)) ?: 0;
                    if($statsByWeekDayAndHour[$weekDay][$hourDay] > $statsByWeekDayAndHourMax) {
                        $statsByWeekDayAndHourMax = $statsByWeekDayAndHour[$weekDay][$hourDay];
                    }

                    $statsByWeekDay->days[$weekDay] += $statsByWeekDayAndHour[$weekDay][$hourDay];
                }
                $statsByWeekDay->total += $statsByWeekDay->days[$weekDay];
            }

            foreach (range(0, 6) as $weekDay) {
                $statsByWeekDay->percentage[$weekDay] = number_format(100 * $statsByWeekDay->days[$weekDay] / $statsByWeekDay->total);
            }

            $stats[$operation] = [
                'total' => (int) $this->statsRepository->get(sprintf(self::TOTAL, $operation)),
                'year' =>  (int) $this->statsRepository->get(sprintf(self::TOTAL_BY_YEAR, $operation, $year)),
                'month' => (int) $this->statsRepository->get(sprintf(self::TOTAL_BY_YEAR_AND_MONTH, $operation, $year, $month)),
                'today' => (int) $this->statsRepository->get(sprintf(self::TOTAL_BY_DATE, $operation, $year, $month, $day)),
                'hour' =>  (int) $this->statsRepository->get(sprintf(self::TOTAL_BY_YEAR_AND_MONTH, $operation, $year, $month, $day, $hour)),
                'statsByWeekday' => $statsByWeekDay,
                'statsByWeekdayAndHour' => $statsByWeekDayAndHour,
                'statsByWeekDayAndHourMax' => $statsByWeekDayAndHourMax
            ];
        }

        return $stats;
    }

    /**
     * @param \DateTimeImmutable $date
     * @return array
     */
    private function splitDate(\DateTimeImmutable $date)
    {
        return explode('-', $date->format('Y-m-d-N-H'));
    }
}