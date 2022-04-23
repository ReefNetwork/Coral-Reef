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

namespace ree_jp\coral_reef\item;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\player\Player;

class DocsBook extends ClickItem
{
    public function __construct()
    {
        parent::__construct(new ItemIdentifier(ItemIds::BOOK, 1), "説明");
    }

    static function onActive(Player $p, ClickItem $item): ItemUseResult
    {
        if (!self::markCoolTime($p, $item)) return ItemUseResult::FAIL();
        $form = new SimpleForm();
        $form->setTitle("Reef Server Docs")->setText("確認したいものを選択してください");
        $form->addElements(
            new ClosureButton("機能解説", null, function () use ($p): void {
                $p->getServer()->dispatchCommand($p, "exe-p wp-view category 104");
            }),
            new ClosureButton("ルール、ガイドライン", null, function () use ($p): void {
                $p->getServer()->dispatchCommand($p, "exe-p wp-view category 103");
            })
        );
        $p->sendForm($form);
        return ItemUseResult::SUCCESS();
    }
}