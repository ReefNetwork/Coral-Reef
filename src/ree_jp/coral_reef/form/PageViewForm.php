<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\form;

use bbo51dog\bboform\element\Dropdown;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\form\ClosureCustomForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PageViewForm
{
    /**
     * @param Player $p
     * @param string $title
     * @param string $label
     * @param string[] $contents
     * @param int $separate
     * @return void
     */
    static function sendForm(Player $p, string $title, string $label, array $contents, int $separate): void
    {
        $separatedContent = array_chunk($contents, $separate);
        self::sendPageForm($p, $title, $label, $separatedContent, 0);
    }

    static function sendPageForm(Player $p, string $title, string $label, array $contents, int $page): void
    {
        if (!isset($contents[$page])) {
            $p->sendMessage("エラーが発生しました");
            return;
        }

        $maxPage = count($contents);
        $pageList = [];
        for ($i = 1; $i <= $maxPage; $i++) {
            $pageList[] = "$i";
        }

        $content = $contents[$page];
        $contentString = "";
        foreach ($content as $string) {
            $contentString .= $string . "\n";
        }

        $pageSelectElement = new Dropdown("表示したいページを選択してください" . TextFormat::DARK_GRAY . "(" . $page + 1 .
            "/$maxPage ページ)", $pageList);
        $form = new ClosureCustomForm(function (Player $p) use ($contents, $label, $title, $pageSelectElement): void {
            $nextPage = intval($pageSelectElement->getSelectedOption()) - 1;
            self::sendPageForm($p, $title, $label, $contents, $nextPage);
        });
        $form->setTitle($title)->addElements(new Label($label), new Label($contentString), $pageSelectElement);
        $p->sendForm($form);
    }
}