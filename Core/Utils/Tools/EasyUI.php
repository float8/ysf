<?php
namespace Core\Utils\Tools {

    class EasyUI
    {
        /**
         * @desc 数据网格
         * @param array $options
         * @param array $fields
         * @param string $id
         * @param string $style
         */
        public static function dataGrid(array $options, array $fields, $id = 'data_grid_list', $style = 'width:100%;display: none;')
        {
            $th = '';
            $loading = /** @lang html */
                '<div style="background: url(\'/public/js/lib/layer/skin/default/loading-2.gif\') no-repeat center;height: 100%;width: 100%;" id="loading"></div>';
            $defaultOptions = array(
                'striped' => 'true',
                'nowrap' => 'true',
                'fitColumns' => 'true',
                'scrollbarSize' => 0,
                'pageSize' => '20',
                'toolbar' => '\'#table_button\'',
                'pagination' => 'true',
                'style' => '{marginTop:\'1px\',position:\'absolute\'}',
                'loader' => "function(param, success, error){require( ['common'], function(common) {common.EasyUILoader(param, success, error ,'{$id}','datagrid');});}"
            );
            //table属性
            $tableDataOptions = self::dataOptions(array_merge($defaultOptions, $options));

            //字段
            if (!empty($fields)) {
                foreach ($fields as $v) {
                    $dataOptions = isset($v['data-options']) ? (array)$v['data-options'] : array();
                    $fieldDataOptions = self::dataOptions(array_merge(array('field' => $v['field']), $dataOptions));

                    $width = isset($v['width']) ? "width=\"{$v['width']}\"" : '';
                    $sortable = isset($v['sortable']) && $v['sortable'] ? "sortable='true'" : '';
                    $other = isset($v['other']) && $v['other'] ? $v['other'] : '';
                    $th .= "<th {$width} data-options=\"{$fieldDataOptions}\" {$sortable} {$other}>{$v['title']}</th>";

                }
            }

            echo $loading . '<table class="easyui-datagrid" id="' . $id . '" style="' . $style . '" data-options="' . $tableDataOptions . '"><thead><tr>' . $th . '</tr></thead></table>';
        }

        /**
         * @desc 生成data-options
         * @param array $options
         * @return string
         */
        public static function dataOptions(array $options)
        {
            $dataOptions = '';
            if (empty($options)) {
                return $dataOptions;
            }
            foreach ($options as $k => $v) {
                switch ($k) {
                    case 'resizeHandle':
                    case 'method':
                    case 'idField':
                    case 'url':
                    case 'loadMsg':
                    case 'pagePosition':
                    case 'sortName':
                    case 'sortOrder':
                    case 'title':
                    case 'field':
                    case 'align':
                    case 'halign':
                    case 'order':
                        $dataOptions .= "{$k}:'{$v}',";
                        break;
                    default:
                        $dataOptions .= "{$k}:{$v},";
                        break;
                }
            }
            return trim($dataOptions, ',');
        }

        /**
         * @desc 数据网格工具条
         * @param array $options
         * @param $title
         * @param array $user_tools
         */
        public static function dataGridTools(array $options, $title, $user_tools = [])
        {
            if (empty($options)) {
                return;
            }
            /**
             * @desc 创建按钮
             * @param $class
             * @param $iconCls
             * @param $title
             * @param string $menuID
             * @return string
             */
            $buildButton = function ($class, $iconCls, $title, $menuID = null) {
                $menu = '';
                $plain = 'plain="true"';
                if (!empty($menuID)) {
                    $menu = "data-options=\"menu:'{$menuID}'\"";
                    $plain = '';
                }
                $buttonHtml = /** @lang html */
                    "<a href=\"javascript:;\" onmouseup=\"\$(this).removeClass('l-btn-focus');\" class=\"%s\" %s iconCls=\"%s\" %s>%s</a>";
                return sprintf($buttonHtml, $class, $menu, $iconCls, $plain, $title);
            };

            $tools = array(
                'add' => $buildButton('easyui-linkbutton add', 'icon-add', '添加'),
                'disabled' => $buildButton('easyui-linkbutton disabled', 'icon-disabled', '禁用'),
                'remove' => $buildButton('easyui-linkbutton remove', 'icon-remove', '删除'),
                'enabled' => $buildButton('easyui-linkbutton enabled', 'icon-ok', '启用'),
                'edit' => $buildButton('easyui-linkbutton edit', 'icon-edit', '编辑'),
                'view' => $buildButton('easyui-linkbutton info', 'icon-view', '查看'),
                'search' => $buildButton('easyui-linkbutton search', 'icon-search', '搜索'),
                'refresh' => $buildButton('easyui-linkbutton reload', 'icon-reload', '刷新'),
                'chart' => $buildButton('easyui-linkbutton chart', 'icon-large-chart', '图表'),
                'excel' => $buildButton('easyui-linkbutton excel', 'icon-excel', '导出'),
                'more' => $buildButton('easyui-menubutton', 'icon-more', '其它', '#other_menu'),
            );
            $html = '';
            foreach ($options as $v) {
                $html .= isset($tools[$v]) ? $tools[$v] : '';
            }
            if(!empty($user_tools) && is_array($user_tools)) {
                foreach ($user_tools as $v) {
                    $html .= $buildButton($v['class'], $v['iconCls'], $v['title'], $v['menuId']);
                }
            }

            if (!empty($html)) {
                echo sprintf('<div id="table_button" title="%s" style="display: none;">%s</div>', $title, $html);
            }
        }

        /**
         * @desc js
         * @param string $script
         */
        public static function script($script)
        {
            echo /** @lang script */
            "<script type=\"text/javascript\">\n{$script}\n</script>";
        }

        /**
         * @desc form表单
         * @param string $name
         * @param string $method
         * @param array $data
         * @param array $validators
         * @return \Core\Utils\Tools\EasyUiForm
         */
        public static function form($name = null, $method = null, $data = array(), array $validators = array())
        {
            return new EasyUiForm($name, $method, $data, $validators);
        }
    }

    class EasyUiForm
    {
        //数据
        private $data = null;
        //form
        private $form = array();
        //form hidden
        private $hiddens = array();
        //表单组
        private $group = array();
        //当前组
        private $nowGroup = 0;
        //当前Tr
        private $nowTr = 0;
        //当前Td
        private $nowTd = 0;
        //form元素
        private $forms = array();
        //验证器
        private $validators = array();

        /**
         * EasyUiForm constructor.
         * @param string $name 表单name
         * @param string $method 表单method
         * @param array $data 表单数据
         * @param array $validators
         */
        public function __construct($name, $method, $data = array(), array $validators = array())
        {
            $this->form['name'] = $name;
            $this->form['id'] = $name;
            $this->form['method'] = $method;
            $this->form['tdTitleWidth'] = 100;
            $this->form['tdFieldWidth'] = 180;
            $this->form['columnNum'] = 1;
            $this->data = $data;
            $this->validators = $validators;
            $this->group = array();
            $this->hiddens = array();
            $this->nowGroup = 0;
            $this->nowTr = 0;
            $this->nowTd = 0;
            $this->forms = array();
        }

        /**
         * @desc 添加Form属性
         * @param $key
         * @param $value
         * @return $this
         */
        public function setFormAttr($key, $value) {
            $this->form[$key] = $value;
            return $this;
        }

        /**
         * @desc 设置td属性
         * @param $tdTitleWidth
         * @param $tdFieldWidth
         * @param $columnNum
         * @return $this
         */
        public function setTdAttr($tdTitleWidth, $tdFieldWidth, $columnNum)
        {
            $this->form['tdTitleWidth'] = $tdTitleWidth;
            $this->form['tdFieldWidth'] = $tdFieldWidth;
            $this->form['columnNum'] = $columnNum;
            return $this;
        }

        /**
         * @desc 设置表单组
         * @param $id
         * @param $title
         * @return $this
         */
        public function setGroup($id, $title)
        {
            $this->group[$id] = array('id' => $id, 'title' => $title);
            $this->nowTr = 0;
            $this->nowGroup = $id;
            return $this;
        }

        /**
         * @desc 设置tr
         * @return $this
         */
        public function setTr()
        {
            $this->nowTr++;
            $this->nowTd = 0;
            return $this;
        }

        /**
         * @desc 设置tr
         * @return EasyUiForm
         */
        public function setTd()
        {
            $this->nowTd++;
            return $this;
        }

        /**
         * @desc 设置字段
         * @param mixed $name
         * @param string $type
         * @param bool $display
         * @param array $attribute
         * @return $this
         */
        public function setField($name, $type = 'text', $display = true, array $attribute = array())
        {
            if(is_callable($name)) {
                $name($name, $type);
            }
            if(empty($name)){
                return $this;
            }
            $attribute['name'] = $name;
            $attribute['type'] = $type;
            $attribute['display'] = $display ? '' : 'none';
            $attribute['validType'] = isset($this->validators[$name]) ? $this->validators[$name] : array();
            $attribute['data-options'] = array();
            if ($type == 'hidden') {
                $this->hiddens[] = $attribute;
            } else {
                $this->forms[$this->nowGroup][$this->nowTr][$this->nowTd][] = $attribute;
            }
            return $this;
        }

        /**
         * @desc 设置option
         * @param $key
         * @param $val
         * @param bool $isHidden
         * @return $this
         */
        public function setAttr($key, $val = null, $isHidden = false)
        {
            if(is_callable($key)) {
                call_user_func($key, $key, $val);
            }
            if(empty($key)){
                return $this;
            }
            if (!$isHidden) {
                $nowFields = $this->forms[$this->nowGroup][$this->nowTr][$this->nowTd];
                $nowFieldsIndex = count($nowFields) - 1;
                $this->forms[$this->nowGroup][$this->nowTr][$this->nowTd][$nowFieldsIndex][$key] = $val;
            } else {
                $nowIndex = count($this->hiddens) - 1;
                $this->hiddens[$nowIndex][$key] = $val;
            }
            return $this;
        }

        /**
         * @desc 设置option
         * @param $key
         * @param $val
         * @return $this
         */
        public function setOption($key, $val)
        {
            $nowFields = $this->forms[$this->nowGroup][$this->nowTr][$this->nowTd];
            $nowFieldsIndex = count($nowFields) - 1;
            $this->forms[$this->nowGroup][$this->nowTr][$this->nowTd][$nowFieldsIndex]['data-options'][$key] = $val;
            return $this;
        }

        /**
         * @desc 设置验证器
         * @param $validator
         * @return $this
         */
        public function setValidator($validator)
        {
            $nowFields = $this->forms[$this->nowGroup][$this->nowTr][$this->nowTd];
            $nowFieldsIndex = count($nowFields) - 1;
            $this->forms[$this->nowGroup][$this->nowTr][$this->nowTd][$nowFieldsIndex]['validType'][] = $validator;
            return $this;
        }

        /**
         * @desc 创建EasyUI
         */
        public function build()
        {
            if (empty($this->forms) && empty($this->hiddens)) {
                return;
            }
            $this->formStart();
            $this->buildGroup();
            $this->formEnd();
            $this->buildSubmit();
        }

        /**
         * @desc form 开始标签
         */
        private function formStart()
        {
            $form = $this->form;
            $form['AUTOCOMPLETE'] = "OFF";
            $this->buildHtmlAttr($form)->label('form', $form, '');
            $this->formHidden();
            echo(empty($this->group) && !empty($this->forms) ? '<table>' : '');
        }

        /**
         * @desc form 结束标签
         */
        private function formEnd()
        {
            echo (empty($this->group) && !empty($this->forms) ? '</table>' : '') . '</form>';
        }

        /**
         * @desc 创建表单组
         */
        public function buildGroup()
        {
            if (empty($this->group)) {
                $this->buildTr(0);
                return;
            }
            foreach ($this->group as $k => $v) {
                $this->groupStart($k);
                $this->buildTr($k);
                $this->groupEnd();
            }
        }

        /**
         * @desc 组 开始标签
         * @param $id
         */
        private function groupStart($id)
        {
            echo /** @lang html */
                '<fieldset style="border: 1px solid #C4C4C4;" id="' . $id . '"><legend>' . $this->group[$id]['title'] . '</legend><table width="100%">';
        }

        /**
         * @desc 组 结束标签
         */
        private function groupEnd()
        {
            echo '</table></fieldset>';
        }

        /**
         * @desc 创建Tr
         * @param string $group
         */
        public function buildTr($group)
        {
            if (empty($this->forms) || !isset($this->forms[$group]) || empty($this->forms[$group])) {
                return;
            }
            foreach ($this->forms[$group] as $v) {
                $this->trStart();
                $this->buildTd($v);
                $this->trEnd();
            }
        }

        /**
         * @desc tr 开始标签
         */
        private function trStart()
        {
            echo '<tr>';
        }

        /**
         * @desc tr 结束标签
         */
        private function trEnd()
        {
            echo '</tr>';
        }

        /**
         * @desc 创建td
         * @param $td
         */
        public function buildTd($td)
        {
            if (empty($td)) {
                return;
            }
            /**
             * @desc 创建字段
             * @param $obj
             * @param $fields
             * @param $data
             */
            $buildFields = function ($obj, $fields, $data) {
                if (empty($fields)) {
                    return;
                }
                $fCnt = count($fields);
                foreach ($fields as $k => $v) {
                    $this->boxStart($v);
                    $this->lableTitle($v, $k, $fCnt);
                    if(isset($v['name']) && isset($data[$v['name']])) {
                        $v['value'] = $data[$v['name']];
                    }
                    $html = '';
                    if(isset($v['html'])){
                        $html = $v['html'];
                        unset($v['html']);
                    }
                    call_user_func(array($obj, "form" . ucfirst($v['type'])), $v, $html);
                    $this->boxEnd();
                }
            };
            $tdCnt = count($td);
            foreach ($td as $k => $v) {
                $this->tdStart($v[0]['title'], $k, $tdCnt);
                $buildFields($this, $v, $this->data);
                $this->tdEnd();
            }
        }

        /**
         * @desc 提交按钮
         */
        private function buildSubmit()
        {
            echo '<table style="padding:0;width: 100%;"><tr><td style="width: 20%;"></td><td style="width: 20%;"></td><td style="width: 20%;"><a href="javascript:void(0)" class="easyui-linkbutton submit_button" data-options="iconCls:\'icon-save\'">提 交</a></td><td style="width: 20%;"></td><td style="width: 20%;"></td></tr></table>';
        }

        /**
         * @desc 一个td钟标题
         * @param array $field
         * @param int $index
         * @param int $cnt
         */
        private function lableTitle($field, $index, $cnt)
        {
            if ($cnt <= 1 || $index == 0 || !isset($field['title']) || empty($field['title'])) {
                return;
            }
            echo sprintf(/** @lang html */
                "<lable class=\"lable-title\">%s</lable>", $field['title']);
        }

        /**
         * @desc 表单box
         * @param array $field
         */
        private function boxStart(&$field)
        {
            echo "<span style=\"margin-right:5px;" . (isset($field['display']) && !empty($field['display']) ? "display:{$field['display']};" : "") . "\">";
            if (isset($field['display'])) {
                unset($field['display']);
            }
        }

        /**
         * @desc 表单box
         */
        private function boxEnd()
        {
            echo "</span>";
        }

        /**
         * @desc td 开始标签
         * @param $title
         * @param $index
         * @param $cnt
         */
        private function tdStart($title, $index, $cnt)
        {
            $cnt = ($this->form['columnNum'] - $cnt) * 2 + 1;
            $style = ($cnt - $index == 1) ? "style=\"width: {$this->form['tdFieldWidth']}px;\"" : '';
            echo "<td style=\"width: {$this->form['tdTitleWidth']}px;text-align:right;padding-right:10px;\">{$title}</td>";
            echo ($cnt > 1) ? "<td colspan=\"{$cnt}\" >" : "<td {$style}>";
        }

        /**
         * @desc td 结束标签
         */
        private function tdEnd()
        {
            echo "</td>";
        }

        /**
         * @desc 创建属性
         * @param $attr
         * @return $this
         */
        private function buildHtmlAttr(&$attr)
        {
            if (isset($attr['title'])) {
                unset($attr['title']);
            }
            if (empty($attr)) {
                return $this;
            }
            if (isset($attr['validType']) && !empty($attr['validType'])) {
                $attr['data-options']['validType'] = "['" . implode("','", $attr['validType']) . "']";
            }

            $buildDataOptions = function (& $attr) {
                if (!isset($attr['data-options'])) {
                    return;
                }
                if (empty($attr['data-options'])) {
                    unset($attr['data-options']);
                    return;
                }
                $attr['data-options'] = EasyUI::dataOptions($attr['data-options']);
            };

            $buildDataOptions($attr);
            if (isset($attr['validType'])) {
                unset($attr['validType']);
            }
            $attrStr = '';
            foreach ($attr as $k => $v) {
                $v = is_bool($v) ? var_export($v, true) : $v;
                $attrStr .= "{$k}=\"{$v}\" ";
            }
            $attr = $attrStr;
            return $this;
        }

        /**
         * @desc 添加样式
         * @param $attr
         * @param $class
         * @return $this
         */
        private function addClass(&$attr, $class)
        {
            if (!isset($attr['class']) || empty($attr['class'])) {
                $attr['class'] = $class;
            } else {
                $attr['class'] = "{$class} {$attr['class']}";
            }
            return $this;
        }

        /**
         * @desc 添加属性
         * @param $attr
         * @param $key
         * @param $val
         * @return $this
         */
        private function addOption(&$attr, $key, $val)
        {
            $attr[$key] = $val;
            return $this;
        }

        /**
         * @desc 添加ata-options属性
         * @param $attr
         * @param $key
         * @param $val
         * @return $this
         */
        private function addDataOption(&$attr, $key, $val)
        {
            $attr['data-options'][$key] = $val;
            return $this;
        }

        /**
         * @desc 生成标签
         * @param $label
         * @param $attr
         * @param string $html
         * @param string $close
         */
        public function label($label, $attr, $html, $close = '/')
        {
            echo "<{$label} {$attr} {$close}>{$html}";
        }


        /**
         * @desc 创建div标签
         * @param $attr
         * @param $html
         */
        public function formSpan($attr, $html)
        {
            $value = '';
            if(isset($attr['value'])) {
                $value = $attr['value'];
                unset($attr['value']);
                unset($attr['type']);
            }
            $this->buildHtmlAttr($attr)
                ->label('span', $attr, $html, ">{$value}</span");
        }

        /**
         * @desc 创建text标签
         * @param $attr
         * @param $html
         */
        public function formText($attr, $html)
        {
            $this->addClass($attr, 'easyui-textbox text-box')
                ->buildHtmlAttr($attr)
                ->label('input', $attr, $html);
        }

        /**
         * @desc 列表框
         * @param $attr
         * @param $html
         */
        public function formCombobox($attr, $html)
        {
            if(!isset($attr['data'])){
                $this->addClass($attr, 'easyui-combobox')
                    ->addOption($attr, 'valueField', (isset($attr['valueField']) ? $attr['valueField'] : 'value'))
                    ->addOption($attr, 'textField', (isset($attr['textField']) ? $attr['textField'] : 'text'))
                    ->addOption($attr, 'type', 'text')
                    ->addDataOption($attr, 'loader', "function(param, success, error){require( ['common'], function(common) {common.EasyUILoader(param, success, error ,'{$attr['id']}','combobox');});}")
                    ->buildHtmlAttr($attr)
                    ->label('input', $attr, $html);
                return ;
            }

            $data = $attr['data'];
            unset($attr['data']);
            $options = '';
            $textField = isset($attr['textField']) ? $attr['textField'] : 'text';
            $valueField = isset($attr['valueField']) ? $attr['valueField'] : 'value';
            foreach ($data as $v){
                $selected =  (isset($attr['value']) && $attr['value'] == $v[$valueField]) ? 'selected="selected"' : '';
                $options .= "<option value=\"{$v[$valueField]}\" {$selected}>{$v[$textField]}</option>";
            }
            $this->addClass($attr, 'easyui-combobox')
                ->buildHtmlAttr($attr)
                ->label('select', $attr, $html, ">{$options}</select");
        }

        /**
         * @desc 数字表单
         * @param $attr
         * @param $html
         */
        public function formNumberbox($attr, $html)
        {
            $this->addClass($attr, 'easyui-numberbox')
                ->addOption($attr, 'type', 'text')
                ->buildHtmlAttr($attr)
                ->label('input', $attr, $html);
        }

        /**
         * @desc 日期表单
         * @param $attr
         * @param $html
         */
        public function formDatebox($attr, $html)
        {
            $this->addClass($attr, 'easyui-datebox')
                ->addOption($attr, 'type', 'text')
                ->buildHtmlAttr($attr)
                ->label('input', $attr, $html);
        }

        /**
         * @desc 日期时间表单
         * @param $attr
         * @param $html
         */
        public function formDatetimebox($attr, $html)
        {
            $this->addClass($attr, 'easyui-datetimebox')
                ->addOption($attr, 'valueField', 'value')
                ->addOption($attr, 'textField', 'text')
                ->addOption($attr, 'type', 'text')
                ->buildHtmlAttr($attr)
                ->label('input', $attr, $html);
        }

        /**
         * @desc 文本域表单
         * @param $attr
         * @param $html
         */
        public function formTextarea($attr, $html)
        {
            $value = '';
            if(isset($attr['value'])) {
                $value = $attr['value'];
                unset($attr['value']);
                unset($attr['type']);
            }
            $this->addClass($attr, 'textbox textbox-text')
                ->buildHtmlAttr($attr)
                ->label('textarea', $attr, $html,">{$value}</textarea");
        }

        /**
         * @desc 单选框
         * @param $attr
         * @param $html
         */
        public function formRadio($attr, $html)
        {
        }

        /**
         * @desc 多选框
         * @param $attr
         * @param $html
         */
        public function formCheckbox($attr, $html)
        {
        }

        /**
         * @desc 文件域表单
         * @param $attr
         * @param $html
         */
        public function formFile($attr, $html)
        {
            echo '<div class="upload">';
            $attrText = $attr;
            $this->addOption($attrText, 'type', 'hidden')
                ->buildHtmlAttr($attrText)
                ->label('input', $attrText);

            $img = empty($attr['value']) ? "/public/images/timg.jpg" : IMAGES_SITE . $attr['value'];
            echo "<div class='img_show'><a href='{$img}' target='_blank'><img height='40' data-src='{$img}' src='{$img}'></a><a href='javascript:void(0)' class='delete_file'>x</a></div>";

            $attrButton = $attr;
            $this->addOption($attrButton, 'type', 'button')
                ->addOption($attrButton, 'data-field_id', $attrButton['id'])
                ->addOption($attrButton, 'id', $attrButton['data-button_id'])
                ->addOption($attrButton, 'name', $attrButton['data-button_id'])
                ->addOption($attrButton, 'value', '')
                ->buildHtmlAttr($attrButton)
                ->label('input', $attrButton, $html);
            echo '</div>';
        }

        /**
         * @desc 隐藏域
         */
        public function formHidden()
        {
            if (empty($this->hiddens)) {
                return;
            }
            foreach ($this->hiddens as $v) {
                if(isset($v['name']) && isset($this->data[$v['name']])) {
                    $v['value'] = $this->data[$v['name']];
                }
                $this->buildHtmlAttr($v)->label('input', $v, '');
            }
        }
    }
}