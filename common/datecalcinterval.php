<?php

/// Класс для получения интервалов дат
/// В отличеие от DateTime / DateTimeInterval, корректно работает с вычитанием месяцев
class DateCalcInterval {
	var $base_time;		//< Дата в unixtime, от которой ведём отсчёт
	var $start;		//< Рассчитанная дата начала интервала
	var $end;		//< рассчитанная дата конца интервала
	
	/// Конструктор
	public function __construct() {
		$this->base_time = time();
		$this->start = 0;
		$this->end = 0;
	}
	
	/// Устанавливает начальную дату для точки отсчёта
	public function setBaseDate($date) {
		$this->base_time = $date;
	}
	

	/// Рассчитать интервал на X дней от точки отсчёта
	public function calcXDaysBack($x) {
		$this->start = $this->base_time - $x*60*60*24;
		$this->end = $this->base_time;
	}
	
	/// Рассчитать интервал на X месяцев от точки отсчёта
	public function calcXMonthsBack($x) {
		$date = getdate($this->base_time);
		$date['mon'] -= $x;
		while($date['mon'] <= 0) {
			$date['year']--;
			$date['mon'] += 12;
		}
		$days = cal_days_in_month(CAL_GREGORIAN, $date['mon'], $date['year']);
		if($date['mday']>$days)
			$date['mday'] = $days;
		$this->start = mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']);
		$this->end = $this->base_time;
	}
	
	/// Рассчитать интервал на X месяцев от точки отсчёта
	public function calcXYearsBack($x) {
		$date = getdate($this->base_time);
		$date['year'] -= $x;
		$days = cal_days_in_month(CAL_GREGORIAN, $date['mon'], $date['year']);
		if($date['mday']>$days)	// Високосные
			$date['mday'] = $days;
		$this->start = mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']);
		$this->end = $this->base_time;
	}
	
	/// Рассчитать интервал - предыдущий месяц
	public function calcPrevMonth() {
		$date = getdate($this->base_time);
		$date['mon']--;
		if($date['mon']<=0) {
			$date['year']--;
			$date['mon'] += 12;
		}
		$days = cal_days_in_month(CAL_GREGORIAN, $date['mon'], $date['year']);
		$this->start = mktime(0, 0, 0, $date['mon'], 1, $date['year']);
		$this->end = mktime(23, 59, 59, $date['mon'], $days, $date['year']);
	}
	
	/// Рассчитать инервал - предыдущий квартал
	public function calcPrevQuarter() {
		$date = getdate($this->base_time);
		$quarter = intval( ($date['mon']-1)/3 );
		$year = $date['year'];
		$quarter--;
		if($quarter<0) {
			$quarter = 3;
			$year--;
		}
		$days = cal_days_in_month(CAL_GREGORIAN, $quarter*3 + 3, $year);
		$this->start = mktime(0, 0, 0, $quarter*3 + 1, 1, $year);
		$this->end = mktime(23, 59, 59, $quarter*3 + 3, $days, $year);
	}
	
	/// Рассчитать инервал - предыдущее полугодие
	public function calcPrevHalfyear() {
		$date = getdate($this->base_time);
		$halfyear = intval( ($date['mon']-1)/6 );
		$year = $date['year'];
		$halfyear--;
		if($halfyear<0) {
			$halfyear = 1;
			$year--;
		}
		$days = cal_days_in_month(CAL_GREGORIAN, $halfyear*6 + 6, $year);
		$this->start = mktime(0, 0, 0, $halfyear*6 + 1, 1, $year);
		$this->end = mktime(23, 59, 59, $halfyear*6 + 6, $days, $year);
	}
	
	/// Рассчитать инервал - предыдущий год
	public function calcPrevYear() {
		$date = getdate($this->base_time);
		$date['year']--;
		$days = cal_days_in_month(CAL_GREGORIAN, 12, $date['year']);
		$this->start = mktime(0, 0, 0, 1, 1, $date['year']);
		$this->end = mktime(23, 59, 59, 12, $days, $date['year']);
	}
};

?>
