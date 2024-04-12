<?php
namespace App\Modules\Core;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class Core{
    private $coreConfigExceptions = [
    ];
    private $coreConfigRepository;

    public function is_empty_date($date)
    {
        return preg_replace('#[ 0:-]#', '', $date) === '';
    }

    /**
     * Format date using current channel.
     *
     * @param  \Illuminate\Support\Carbon|string|null  $date
     * @param  string  $format
     * @return string
     */
    public function formatDate($date = null, $format = 'd-m-Y H:i:s')
    {

        if (is_null($date)) {
            $date = Carbon::now();
        }

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date->format($format);
    }

    /**
     * Retrieve information from payment configuration.
     *
     * @param  string  $field
     * @param  int|string|null  $channelId
     * @param  string|null  $locale
     * @return mixed
     */
    public function getConfigData($field, $channel = null, $locale = null)
    {
        static $loadedConfigs = [];

        if (
            array_key_exists($field, $loadedConfigs)
            && ! in_array($field, $this->coreConfigExceptions)
        ) {
            $coreConfigValue = $loadedConfigs[$field];
        } else {
            $loadedConfigs[$field] = $coreConfigValue = $this->getCoreConfigValue($field, $channel, $locale);
        }

        if (! $coreConfigValue) {
            return $this->getDefaultConfig($field);
        }

        return $coreConfigValue->value;
    }

    /**
     * Returns time intervals.
     *
     * @param  \Illuminate\Support\Carbon  $startDate
     * @param  \Illuminate\Support\Carbon  $endDate
     * @return array
     */
    public function getTimeInterval($startDate, $endDate)
    {
        $timeIntervals = [];

        $totalDays = $startDate->diffInDays($endDate) + 1;
        $totalMonths = $startDate->diffInMonths($endDate) + 1;

        $startWeekDay = Carbon::createFromTimeString($this->xWeekRange($startDate, 0) . ' 00:00:01');
        $endWeekDay = Carbon::createFromTimeString($this->xWeekRange($endDate, 1) . ' 23:59:59');
        $totalWeeks = $startWeekDay->diffInWeeks($endWeekDay);

        if ($totalMonths > 5) {
            for ($i = 0; $i < $totalMonths; $i++) {
                $date = clone $startDate;
                $date->addMonths($i);

                $start = Carbon::createFromTimeString($date->format('Y-m-d') . ' 00:00:01');
                $end = $totalMonths - 1 == $i
                    ? $endDate
                    : Carbon::createFromTimeString($date->addMonth()->subDay()->format('Y-m-d') . ' 23:59:59');

                $timeIntervals[] = ['start' => $start, 'end' => $end, 'formattedDate' => $date->format('M')];
            }
        } elseif ($totalWeeks > 6) {
            for ($i = 0; $i < $totalWeeks; $i++) {
                $date = clone $startDate;
                $date->addWeeks($i);

                $start = $i == 0
                    ? $startDate
                    : Carbon::createFromTimeString($this->xWeekRange($date, 0) . ' 00:00:01');
                $end = $totalWeeks - 1 == $i
                    ? $endDate
                    : Carbon::createFromTimeString($this->xWeekRange($date->subDay(), 1) . ' 23:59:59');

                $timeIntervals[] = ['start' => $start, 'end' => $end, 'formattedDate' => $date->format('d M')];
            }
        } else {
            for ($i = 0; $i < $totalDays; $i++) {
                $date = clone $startDate;
                $date->addDays($i);

                $start = Carbon::createFromTimeString($date->format('Y-m-d') . ' 00:00:01');
                $end = Carbon::createFromTimeString($date->format('Y-m-d') . ' 23:59:59');

                $timeIntervals[] = ['start' => $start, 'end' => $end, 'formattedDate' => $date->format('d M')];
            }
        }

        return $timeIntervals;
    }

    /**
     * Week range.
     *
     * @param  string  $date
     * @param  int  $day
     * @return string
     */
    public function xWeekRange($date, $day)
    {
        $ts = strtotime($date);

        if (! $day) {
            $start = (date('D', $ts) == 'Sun') ? $ts : strtotime('last sunday', $ts);

            return date('Y-m-d', $start);
        } else {
            $end = (date('D', $ts) == 'Sat') ? $ts : strtotime('next saturday', $ts);

            return date('Y-m-d', $end);
        }
    }

    /**
     * Method to sort through the acl items and put them in order.
     *
     * @param  array  $items
     * @return array
     */
    public function sortItems($items)
    {
        foreach ($items as &$item) {
            if (count($item['children'])) {
                $item['children'] = $this->sortItems($item['children']);
            }
        }

        usort($items, function ($a, $b) {
            if ($a['sort'] == $b['sort']) {
                return 0;
            }

            return ($a['sort'] < $b['sort']) ? -1 : 1;
        });

        return $this->convertToAssociativeArray($items);
    }

    /**
     * Get config field.
     *
     * @param  string  $fieldName
     * @return array
     */
    public function getConfigField($fieldName)
    {
        foreach (config('core') as $coreData) {
            if (! isset($coreData['fields'])) {
                continue;
            }

            foreach ($coreData['fields'] as $field) {
                $name = $coreData['key'] . '.' . $field['name'];

                if ($name == $fieldName) {
                    return $field;
                }
            }
        }
    }

    /**
     * Convert to associative array.
     *
     * @param  array  $items
     * @return array
     */
    public function convertToAssociativeArray($items)
    {
        foreach ($items as $key1 => $level1) {
            unset($items[$key1]);

            $items[$level1['key']] = $level1;

            if (! count($level1['children'])) {
                continue;
            }

            foreach ($level1['children'] as $key2 => $level2) {
                $temp2 = explode('.', $level2['key']);

                $finalKey2 = end($temp2);

                unset($items[$level1['key']]['children'][$key2]);

                $items[$level1['key']]['children'][$finalKey2] = $level2;

                if (! count($level2['children'])) {
                    continue;
                }

                foreach ($level2['children'] as $key3 => $level3) {
                    $temp3 = explode('.', $level3['key']);

                    $finalKey3 = end($temp3);

                    unset($items[$level1['key']]['children'][$finalKey2]['children'][$key3]);

                    $items[$level1['key']]['children'][$finalKey2]['children'][$finalKey3] = $level3;
                }
            }
        }

        return $items;
    }

    /**
     * Array set.
     *
     * @param  array  $items
     * @param  string  $key
     * @param  string|int|float  $value
     * @return array
     */
    public function array_set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);
        $count = count($keys);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (
                ! isset($array[$key])
                || ! is_array($array[$key])
            ) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $finalKey = array_shift($keys);

        if (isset($array[$finalKey])) {
            $array[$finalKey] = $this->arrayMerge($array[$finalKey], $value);
        } else {
            $array[$finalKey] = $value;
        }

        return $array;
    }

    /**
     * Convert empty strings to null.
     *
     * @param  array  $array1
     * @return array
     */
    public function convertEmptyStringsToNull($array)
    {
        foreach ($array as $key => $value) {
            if ($value == '' || $value == 'null') {
                $array[$key] = null;
            }
        }

        return $array;
    }

    /**
     * Create singleton object through single facade.
     *
     * @param  string  $className
     * @return object
     */
    public function getSingletonInstance($className)
    {
        static $instance = [];

        if (array_key_exists($className, $instance)) {
            return $instance[$className];
        }

        return $instance[$className] = app($className);
    }

    /**
     * Array merge.
     *
     * @param  array  $array1
     * @param  array  $array2
     * @return array
     */
    protected function arrayMerge(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (
                is_array($value)
                && isset($merged[$key])
                && is_array($merged[$key])
            ) {
                $merged[$key] = $this->arrayMerge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Get core config values.
     *
     * @param  mixed  $field
     * @param  mixed  $channel
     * @param  mixed  $locale
     * @return mixed
     */
    protected function getCoreConfigValue($field, $channel, $locale)
    {
        $fields = $this->getConfigField($field);
        
        $coreConfigValue = $this->coreConfigRepository->findOneWhere([
            'code' => $field,
        ]);

        return $coreConfigValue;
    }

    /**
     * Get default config.
     *
     * @param  string  $field
     * @return mixed
     */
    protected function getDefaultConfig($field)
    {
        $configFieldInfo = $this->getConfigField($field);

        $fields = explode('.', $field);

        array_shift($fields);

        $field = implode('.', $fields);

        return Config::get($field, $configFieldInfo['default'] ?? null);
    }

    /**
     * Get max upload size from the php.ini file.
     *
     * @return string
     */
    public function getMaxUploadSize()
    {
        return ini_get('upload_max_filesize');
    }
}