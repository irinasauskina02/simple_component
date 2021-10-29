<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
    
<table>
    <tr>
        <th></th>
        <th></th>
    </tr>
    <?foreach($arResult['ITEMS'] as $item) {?>
        <tr>
            <td><?=$item["NAME"]?></td>
            <td><?=$item["PRICE"]?></td>
        </tr>
    <?}?>            
</table>
<?=$arResult['NAV_STRING'];?> 
