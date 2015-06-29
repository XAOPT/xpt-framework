<?php
class ClassCharts
{
	//Global params chart
	public $div  = '';
	public $title = '';
	public $toolTipTemplate = null;
	public $data = array();
	public $params = array();


	//Params for each line
	public $type = array('spline', 'line', 'column', 'pie');
	public $colors = array('#369ead','#7f6084','#c24642','#a2d1cf','#86b402','#6dbceb','#c8b631','#52514e','#369ead','#7f6084');
	public $showInLegend = array(false);
	public $legendText = array();
	public $name = array();
	public $markerType = array('circle', 'square');
	public $markerColor = "#FFFFFF";
	public $markerSize = 7;

	public function getChart()
	{
		$data = array();

		foreach ($this->data as $k => $v)
		{
			if (isset($this->toolTipTemplate[$k]))
			{
				$this->toolTipTemplate[$k] = str_replace('{color}', $this->colors[$k], $this->toolTipTemplate[$k]);
			}

			$data[$k] = array(
				"type"				=> isset($this->type[$k]) ? $this->type[$k] : 'spline',
				"lineThickness"		=> 2,
				"markerSize"		=> $this->markerSize,
				"markerColor"		=> $this->markerColor,
				"markerBorderThickness" => 1,
				"markerBorderColor"	=> isset($this->colors[$k]) ? $this->colors[$k] : null,
				"color"				=> isset($this->colors[$k]) ? $this->colors[$k] : null,
				"showInLegend"		=> isset($this->showInLegend[$k]) ? $this->showInLegend[$k] : false,
				"legendText"		=> isset($this->legendText[$k]) ? $this->legendText[$k] : null,
				"name"				=> isset($this->name[$k]) ? $this->name[$k] : null,
				"toolTipContent"    => isset($this->toolTipTemplate[$k]) ? $this->toolTipTemplate[$k] : null,
				"markerType"		=> isset($this->markerType[$k]) ? $this->markerType[$k] : 'circle',
			);

			$x = 0;
			foreach ($v as $i => $p)
			{
				$data[$k]['dataPoints'][$i] = array(
					'x' => $x++,
					'y' => $p['y'],
					'label' => isset($p['x']) ? $p['x'] : $x,
					'legendText' => isset($p['x']) ? $p['x'] : null,
					'indexLabel' => isset($p['index']) ? $p['index'] : null
				);

				if(isset($p['toolTipData'])){
						$data[$k]['dataPoints'][$i] = array_merge($data[$k]['dataPoints'][$i], $p['toolTipData']);
				}
			}
		}

		$chart = array(
			'toolTip'=>array('content'=>$this->toolTipTemplate, 'shared'=>true),
			'title'=>array('text'=>$this->title),
			'data'=>$data
		);

		if($this->params){
			$chart = array_merge($chart, $this->params);
		}

		$chart['axisY']['minimum'] = 0;

		echo "new CanvasJS.Chart('".$this->div."', ".json_encode($chart).").render();";
		exit;
	}

	public function addLine($data = array(), $params = array())
	{
		$index = count($this->data);

		$this->data[$index] = $data;
		$this->type[$index] = "spline";
		$this->markerType[$index] = "circle";

		if (!empty($params))
		foreach ($params as $param => $value)
		{
			switch ($param)
			{
				case "legend":
					$this->legendText[$index]   = $value;
					$this->name[$index]         = $value;
					$this->showInLegend[$index] = true;
					break;
				case "hiddenY":
					if ($value)
						$this->params['axisY']['labelFontSize'] = 0;
					break;
				case "toolTipTemplate":
					$this->toolTipTemplate[$index]   = $value;
					break;
				case "color":
					$this->colors[$index]   = $value;
					break;
			}
		}
	}

	public function addPie($data = array(), $params = array())
	{
		$this->data[0][] = $data;
		$this->colors = array();
		$this->type[0]   = "pie";
	}

	public function addColumns($data = array(), $params = array())
	{
		$index = count($this->data);

		$this->data[$index] = $data;
		$this->type[$index] = "column";
		$this->showInLegend[$index] = true;

		if (!empty($params))
		foreach ($params as $param => $value)
		{
			switch ($param)
			{
				case "toolTipTemplate":
					$this->toolTipTemplate[$index]   = $value;
					break;
				case "color":
					$this->colors[$index]   = $value;
					break;
			}
		}
	}

	public function draw()
	{
		$this->getChart();
		echo "new CanvasJS.Chart('".$this->div."', ".json_encode($chart).").render();";
		exit;
	}
}