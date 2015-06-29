<?php

class classPagination
{
	var $query_string;

	var $url_start;   ## начало ссылки
	var $url_end;     ## конец ссылки
	var $url_default; ## ссылка на первую страницу

	var $current_page = 1;  ## текущая страница
	var $per_page     = 0;  ## количество объектво на странице
	var $total        = 0;  ## общее количество объектов
	var $total_pages  = 1;  ## сколько всего страниц
	var $per_selector = array(); ## array(25, 50, 100) - если задан, то выводится селект для выбора количества объектов на странице
	var $cookie_name  = 'limit'; ## имя куки для хранения величины количества объектов на странице

	private function set_per_page()
	{
		if (!$this->per_page)
		{
			if (isset($this->cookie_name) && isset($_COOKIE[$this->cookie_name]) && $_COOKIE[$this->cookie_name])
				$this->per_page = $_COOKIE[$this->cookie_name];
			else if (!empty($this->per_selector))
				$this->per_page = $this->per_selector[0];
			else
				$this->per_page = 10;
		}

		return;
	}

	function get_limit_start()
	{
		$this->set_per_page();
		return ($this->current_page - 1) * $this->per_page;
	}

	function get_limit_count()
	{
		$this->set_per_page();
		return $this->per_page;
	}

	function draw()
	{
		$this->set_per_page();

		if ($this->total && $this->per_page)
			$this->total_pages = ceil($this->total/$this->per_page);

		$first_page = ($this->current_page > 5)?($this->current_page - 4):1;
		$last_page  = ($this->current_page + 4 > $this->total_pages)?$this->total_pages:($this->current_page+4);
		$is_prev    = ($this->current_page > 1)?true:false;
		$is_next    = ($this->current_page < $this->total_pages)?true:false;

		$output = "<div id='pagination'>";

		if ($is_prev)
		{
			$previous = $this->current_page - 1;
			$output .= "<a href='{$this->url_start}{$previous}{$this->url_end}' class='pagelinknav'>&larr; Назад</a>";
		}

		if ($first_page != 1)
		{
			if ($this->url_default)
				$temp_link = $this->url_default;
			else
				$temp_link = "{$this->url_start}1{$this->url_end}";

			$output .= "<div class='page-back'><a href='{$temp_link}' class='pagelink'>1</a></div>";

			if ($first_page != 2)
				$output .= "<span>...</span>";
		}

		for ($i = $first_page; $i <= $last_page; $i++)
		{
			if ($i != 1 || !$this->url_default)
				$temp_link = "{$this->url_start}{$i}{$this->url_end}";
			else
				$temp_link = $this->url_default;

			$output .= "<div class='page-back'><a href='{$temp_link}' rel='{$i}' class='pagelink ";
			$output .= ($this->current_page == $i)?"active":'';
			$output .= "'>{$i}</a></div>";
		}

		if ($last_page != $this->total_pages)
		{
			if (($last_page+1) != $this->total_pages)
				$output .= "<span>...</span>";

			$output .= "<div class='page-back'><a href='{$this->url_start}{$this->total_pages}{$this->url_end}' class='pagelink' rel='{$this->total_pages}'>{$this->total_pages}</a></div>";
		}

		## кнопка вперёд
		if ($is_next)
		{
			$next = $this->current_page + 1;
			$output .= "<a href='{$this->url_start}{$next}{$this->url_end}' class='pagelinknav'>Далее &rarr;</a>";
		}

		## выбор количества объектов на странице
		if (!empty($this->per_selector))
		{
			$output .= "<select onChange=\"document.cookie='{$this->cookie_name}='+this.value+'; path=/; expires=Thu, 23-Jul-2019 20:10:00 GMT';document.location.href='{$this->url_default}'\">";
			foreach ($this->per_selector as $k => $v)
			{
				$selected = ($v == $this->per_page)?'selected':'';
				$output .= "<option value='{$v}' {$selected}>{$v}</option>";
			}
			$output .= '</select>';
		}

		$output .= '</div>';

		return $output;
	}
}
?>
