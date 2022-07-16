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

namespace ree_jp\coral_reef\form\ranking;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\SimpleForm;
use Generator;
use pocketmine\player\Player;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\form\PageViewForm;
use ree_jp\coral_reef\sql\model\LiteUserModel;
use ree_jp\coral_reef\sql\repo\UserRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use SOFe\AwaitGenerator\Await;

class RankingForm
{
    static function sendForm(RepositoryPool $pool, AccountStore $store, Player $p): void
    {
        $form = (new SimpleForm())
            ->setTitle("Ranking")
            ->setText("表示したいランキングを選択してください");
        $form->addElements(
            new ClosureButton("経験値ランキング", null,
                function () use ($pool, $p): void {
                    self::sendAllExperienceForm($pool, $p);
                }
            ),
            new ClosureButton("毎日採掘量ランキング", null,
                function () use ($store, $p, $pool): void {
                    DigRankingForm::sendDailyDigForm($pool, $store, $p);
                }
            ),
            new ClosureButton("週間採掘量ランキング", null,
                function () use ($store, $p, $pool): void {
                    DigRankingForm::sendWeeklyDigForm($pool, $store, $p);
                }
            )
        );

        $p->sendForm($form);
    }

    static function sendAllExperienceForm(RepositoryPool $pool, Player $p): void
    {
        Await::f2c(function () use ($p, $pool): Generator {
            /** @var UserRepository */
            $repo = $pool->get(UserRepository::class);
            /** @var LiteUserModel[] */
            $userModels = yield from $repo->getAllUserData();

            if (!$p->isOnline()) return;
            $list[] = [];
            foreach ($userModels as $userModel) {
                if ($userModel->xuid === "0") continue; // サーバー管理用アカウントをランキングに入れない
                $list[$userModel->experience][] = $userModel->name;
            }
            krsort($list, SORT_NUMERIC);
            $content = [];
            $ranking = 1;
            $my = 0;
            foreach ($list as $exp => $users) {
                $equal = 0;
                foreach ($users as $user) {
                    if ($user === $p->getName()) {
                        $my = $ranking;
                    }
                    $equal++;
                    $content[] = "$ranking 位: $user さん($exp)";
                }
                $ranking += $equal;
            }
            PageViewForm::sendForm($p, "ランキング", "あなたは" . $my . "位です\n\n", $content, 100);
        });
    }
}
