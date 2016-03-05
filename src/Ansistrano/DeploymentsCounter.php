<?php

namespace Ansistrano;

class DeploymentsCounter
{
    /**
     * @var StatsRepository
     */
    private $statsRepository;

    const TOTAL = 'ansistrano:deployments:total';
    const TOTAL_BY_MONTH = 'ansistrano:deployments:total:month:%s';
    const TOTAL_BY_MONTH_AND_DAY = 'ansistrano:deployments:total:month:%s:day:%s';
    const TOTAL_BY_MONT_DAY_HOUR = 'ansistrano:deployments:total:month:%s:day:%s:hour:%s';
    const TOTAL_BY_YEAR = 'ansistrano:deployments:total:year:%s';
    const TOTAL_BY_YEAR_AND_MONTH = 'ansistrano:deployments:total:year:%s:month:%s';
    const TOTAL_BY_DATE = 'ansistrano:deployments:total:year:%s:month:%s:day:%s';
    const TOTAL_BY_DATE_AND_HOUR = 'ansistrano:deployments:total:year:%s:month:%s:day:%s:hour:%s';
    const TOTAL_BY_WEEKDAY = 'ansistrano:deployments:total:weekday:%s';
    const TOTAL_BY_WEEKDAY_AND_HOUR = 'ansistrano:deployments:total:weekday:%s:hour:%s';

    public function __construct(StatsRepository $statsRepository)
    {
        $this->statsRepository = $statsRepository;
    }

    public function addDeployment(\DateTimeImmutable $date)
    {
        list($year, $month, $day, $dayOfWeek, $hour) = $this->splitDate($date);

        $this->statsRepository->increment(sprintf(self::TOTAL));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_MONTH,        $month));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_MONTH_AND_DAY, $month, $day));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_MONT_DAY_HOUR, $month, $day, $hour));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_YEAR, $year));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_YEAR_AND_MONTH, $year, $month));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_DATE, $year, $month, $day));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_DATE_AND_HOUR, $year, $month, $day, $hour));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_WEEKDAY, $dayOfWeek));
        $this->statsRepository->increment(sprintf(self::TOTAL_BY_WEEKDAY_AND_HOUR, $dayOfWeek, $hour));
    }

    public function statsFor(\DateTimeImmutable $date)
    {
        list($year, $month, $day, $dayOfWeek, $hour) = $this->splitDate($date);

        $statsByWeekDayAndHour = [];
        $statsByWeekDayAndHourMax = 0;

        $statsByWeekDay = new \stdClass();
        $statsByWeekDay->percentage = [];
        $statsByWeekDay->days = [];
        $statsByWeekDay->total = 0;

        foreach (range(0, 6) as $weekDay) {
            $statsByWeekDay->days[$weekDay] = 0;
            foreach (range(0, 23) as $hourDay) {
                $statsByWeekDayAndHour[$weekDay][$hourDay] = $this->statsRepository->get(sprintf(self::TOTAL_BY_WEEKDAY_AND_HOUR, $weekDay, $hourDay)) ?: 0;
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

        return [
            'total' => (int) $this->statsRepository->get(self::TOTAL),
            'year' => (int) $this->statsRepository->get(sprintf(self::TOTAL_BY_YEAR, $year)),
            'month' => (int) $this->statsRepository->get(sprintf(self::TOTAL_BY_YEAR_AND_MONTH, $year, $month)),
            'today' => (int) $this->statsRepository->get(sprintf(self::TOTAL_BY_DATE, $year, $month, $day)),
            'hour' => (int) $this->statsRepository->get(sprintf(self::TOTAL_BY_YEAR_AND_MONTH, $year, $month, $day, $hour)),
            'statsByWeekday' => $statsByWeekDay,
            'statsByWeekdayAndHour' => $statsByWeekDayAndHour,
            'statsByWeekDayAndHourMax' => $statsByWeekDayAndHourMax
        ];
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