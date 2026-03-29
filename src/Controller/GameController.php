<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Player;
use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GameController extends AbstractController
{
    #[Route('/start', name: 'app_start')]
    public function start(EntityManagerInterface $em): Response
    {
        $allCards = $em->getRepository(Card::class)->findAll();
        foreach ($allCards as $card) {
            $card->setLocation('deck');
            $card->setPlayer(null);
        }

        $allGames = $em->getRepository(Game::class)->findAll();
        foreach ($allGames as $game) {
            $game->setCurrentPlayer(null);
            $game->setTopCard(null);
        }
        $em->flush();

        $allPlayers = $em->getRepository(Player::class)->findAll();
        foreach ($allPlayers as $player) {
            $em->remove($player);
        }
        $em->flush();

        $allGames = $em->getRepository(Game::class)->findAll();
        foreach ($allGames as $game) {
            $em->remove($game);
        }
        $em->flush();

        $human = new Player();
        $human->setName('Joueur');
        $human->setIsBot(false);

        $bot1 = new Player();
        $bot1->setName('Bot 1');
        $bot1->setIsBot(true);

        $bot2 = new Player();
        $bot2->setName('Bot 2');
        $bot2->setIsBot(true);

        $bot3 = new Player();
        $bot3->setName('Bot 3');
        $bot3->setIsBot(true);

        $em->persist($human);
        $em->persist($bot1);
        $em->persist($bot2);
        $em->persist($bot3);
        $em->flush();

        $deck = $em->getRepository(Card::class)->findAll();
        shuffle($deck);

        $players = [$human, $bot1, $bot2, $bot3];
        foreach ($players as $player) {
            for ($i = 0; $i < 7; $i++) {
                $card = array_shift($deck);
                $card->setLocation('player');
                $player->addCard($card);
            }
        }

        $topCard = null;
        foreach ($deck as $key => $card) {
            $value = $card->getValue();
            if ($value !== 'X' && $value !== 'S' && $value !== '+2') {
                $topCard = $card;
                unset($deck[$key]);
                break;
            }
        }
        if ($topCard === null) {
            $topCard = array_shift($deck);
        }
        $topCard->setLocation('pile');

        $game = new Game();
        $game->addPlayer($human);
        $game->addPlayer($bot1);
        $game->addPlayer($bot2);
        $game->addPlayer($bot3);
        $game->setCurrentPlayer($human);
        $game->setTopCard($topCard);

        $em->persist($game);
        $em->flush();

        return $this->redirectToRoute('app_play');
    }

    #[Route('/play', name: 'app_play')]
    public function play(EntityManagerInterface $em): Response
    {
        $games = $em->getRepository(Game::class)->findAll();
        if (count($games) === 0) {
            return $this->redirectToRoute('app_start');
        }
        $game = $games[0];

        if ($game->getStatus() === 'finished') {
            return $this->redirectToRoute('app_result');
        }

        $topCard = $game->getTopCard();

        $human = null;
        $bots  = [];
        foreach ($game->getPlayers() as $player) {
            if ($player->isBot() === false) {
                $human = $player;
            } else {
                $bots[] = $player;
            }
        }

        $playableIds = [];
        if ($human !== null && $topCard !== null) {
            foreach ($human->getCards() as $card) {
                if ($game->getPendingDraw() > 0) {
                    if ($card->getValue() === '+2') {
                        $playableIds[] = $card->getId();
                    }
                } else {
                    if ($card->getColor() === $topCard->getColor() || $card->getValue() === $topCard->getValue()) {
                        $playableIds[] = $card->getId();
                    }
                }
            }
        }

        $deckCards = $em->getRepository(Card::class)->findBy(['location' => 'deck']);
        $deckCount = count($deckCards);
        $pendingDraw = $game->getPendingDraw();

        $isPlayerTurn = false;
        $currentPlayer = $game->getCurrentPlayer();
        if ($currentPlayer !== null && $human !== null) {
            if ($currentPlayer->getId() === $human->getId()) {
                $isPlayerTurn = true;
            }
        }

        return $this->render('game/play.html.twig', [
            'game'         => $game,
            'topCard'      => $topCard,
            'human'        => $human,
            'bots'         => $bots,
            'playableIds'  => $playableIds,
            'deckCount'    => $deckCount,
            'isPlayerTurn' => $isPlayerTurn,
            'pendingDraw'  => $pendingDraw,
        ]);
    }

    #[Route('/player', name: 'app_player')]
    public function player(Request $request, EntityManagerInterface $em): Response
    {
        $games = $em->getRepository(Game::class)->findAll();
        if (count($games) === 0) {
            return $this->redirectToRoute('app_start');
        }
        $game = $games[0];

        $human = null;
        foreach ($game->getPlayers() as $player) {
            if ($player->isBot() === false) {
                $human = $player;
                break;
            }
        }

        $currentPlayer = $game->getCurrentPlayer();
        if ($human === null || $currentPlayer === null || $currentPlayer->getId() !== $human->getId()) {
            return $this->redirectToRoute('app_play');
        }

        $cardId  = (int) $request->query->get('id', 0);
        $topCard = $game->getTopCard();

        if ($cardId === 0) {
            $pendingDraw = $game->getPendingDraw();
            if ($pendingDraw > 0) {
                $drawCount = $pendingDraw;
            } else {
                $drawCount = 1;
            }
            $game->setPendingDraw(0);

            for ($i = 0; $i < $drawCount; $i++) {
                $deckCards = $em->getRepository(Card::class)->findBy(['location' => 'deck']);
                if (count($deckCards) === 0) {
                    $this->recyclePile($game, $em);
                }
                $drawn = $em->getRepository(Card::class)->findRandomDeckCard();
                if ($drawn !== null) {
                    $drawn->setLocation('player');
                    $human->addCard($drawn);
                }
            }
            $this->advanceTurn($game);
            $em->flush();
        } else {
            $card = $em->getRepository(Card::class)->find($cardId);

            $isPlayable = false;
            if ($card !== null && $topCard !== null) {
                $cardPlayer = $card->getPlayer();
                if ($cardPlayer !== null && $cardPlayer->getId() === $human->getId()) {
                    if ($game->getPendingDraw() > 0) {
                        if ($card->getValue() === '+2') {
                            $isPlayable = true;
                        }
                    } else {
                        if ($card->getColor() === $topCard->getColor() || $card->getValue() === $topCard->getValue()) {
                            $isPlayable = true;
                        }
                    }
                }
            }

            if ($isPlayable) {
                $topCard->setLocation('deck');
                $human->removeCard($card);
                $card->setLocation('pile');
                $card->setPlayer(null);
                $game->setTopCard($card);

                $skip = false;
                if ($card->getValue() === 'S') {
                    if ($game->getDirection() === 'normal') {
                        $game->setDirection('reverse');
                    } else {
                        $game->setDirection('normal');
                    }
                } elseif ($card->getValue() === 'X') {
                    $skip = true;
                } elseif ($card->getValue() === '+2') {
                    $newPending = $game->getPendingDraw() + 2;
                    $game->setPendingDraw($newPending);
                }

                $this->advanceTurn($game);
                if ($skip) {
                    $this->advanceTurn($game);
                }

                if ($human->getCards()->count() === 0) {
                    $game->setStatus('finished');
                }

                $em->flush();
            }
        }

        return $this->redirectToRoute('app_play');
    }

    #[Route('/result', name: 'app_result')]
    public function result(EntityManagerInterface $em): Response
    {
        $games = $em->getRepository(Game::class)->findAll();
        if (count($games) === 0) {
            return $this->redirectToRoute('app_play');
        }
        $game = $games[0];

        if ($game->getStatus() !== 'finished') {
            return $this->redirectToRoute('app_play');
        }

        $winner = null;
        foreach ($game->getPlayers() as $player) {
            if ($player->getCards()->count() === 0) {
                $winner = $player;
                break;
            }
        }

        $humanWon = false;
        if ($winner !== null && $winner->isBot() === false) {
            $humanWon = true;
        }

        return $this->render('game/result.html.twig', [
            'winner'   => $winner,
            'humanWon' => $humanWon,
        ]);
    }

    private function recyclePile(Game $game, EntityManagerInterface $em): void
    {
        $topCard   = $game->getTopCard();
        $pileCards = $em->getRepository(Card::class)->findBy(['location' => 'pile']);

        foreach ($pileCards as $card) {
            if ($topCard !== null && $card->getId() === $topCard->getId()) {
                continue;
            }
            $card->setLocation('deck');
            $card->setPlayer(null);
        }

        $em->flush();
    }

    private function advanceTurn(Game $game): void
    {
        $players = $game->getPlayers()->toArray();

        usort($players, function($a, $b) {
            if ($a->getId() < $b->getId()) {
                return -1;
            } elseif ($a->getId() > $b->getId()) {
                return 1;
            }
            return 0;
        });

        $currentPlayer = $game->getCurrentPlayer();
        $currentId = null;
        if ($currentPlayer !== null) {
            $currentId = $currentPlayer->getId();
        }

        $index = 0;
        for ($i = 0; $i < count($players); $i++) {
            if ($players[$i]->getId() === $currentId) {
                $index = $i;
                break;
            }
        }

        $total = count($players);
        if ($game->getDirection() === 'normal') {
            $nextIndex = ($index + 1) % $total;
        } else {
            $nextIndex = ($index - 1 + $total) % $total;
        }

        $game->setCurrentPlayer($players[$nextIndex]);
    }

    #[Route('/ennemy', name: 'app_ennemy')]
    public function ennemy(Request $request, EntityManagerInterface $em): Response
    {
        $games = $em->getRepository(Game::class)->findAll();
        if (count($games) === 0) {
            return $this->redirectToRoute('app_start');
        }
        $game = $games[0];

        $botId   = (int) $request->query->get('id', 0);
        $bot     = $em->getRepository(Player::class)->find($botId);
        $topCard = $game->getTopCard();

        $currentPlayer = $game->getCurrentPlayer();
        if ($bot === null || $bot->isBot() === false) {
            return $this->redirectToRoute('app_play');
        }
        if ($currentPlayer === null || $currentPlayer->getId() !== $bot->getId()) {
            return $this->redirectToRoute('app_play');
        }

        if ($game->getPendingDraw() > 0) {
            $plusTwo = null;
            foreach ($bot->getCards() as $c) {
                if ($c->getValue() === '+2') {
                    $plusTwo = $c;
                    break;
                }
            }

            if ($plusTwo !== null) {
                $topCard->setLocation('deck');
                $bot->removeCard($plusTwo);
                $plusTwo->setLocation('pile');
                $plusTwo->setPlayer(null);
                $game->setTopCard($plusTwo);
                $newPending = $game->getPendingDraw() + 2;
                $game->setPendingDraw($newPending);
                $this->advanceTurn($game);

                if ($bot->getCards()->count() === 0) {
                    $game->setStatus('finished');
                }
            } else {
                $drawCount = $game->getPendingDraw();
                $game->setPendingDraw(0);
                for ($i = 0; $i < $drawCount; $i++) {
                    $deckCards = $em->getRepository(Card::class)->findBy(['location' => 'deck']);
                    if (count($deckCards) === 0) {
                        $this->recyclePile($game, $em);
                    }
                    $drawn = $em->getRepository(Card::class)->findRandomDeckCard();
                    if ($drawn !== null) {
                        $drawn->setLocation('player');
                        $bot->addCard($drawn);
                    }
                }
                $this->advanceTurn($game);
            }

            $em->flush();
            return $this->redirectToRoute('app_play');
        }

        $playable = [];
        foreach ($bot->getCards() as $c) {
            if ($c->getColor() === $topCard->getColor() || $c->getValue() === $topCard->getValue()) {
                $playable[] = $c;
            }
        }

        if (count($playable) > 0) {
            $randomIndex = rand(0, count($playable) - 1);
            $card = $playable[$randomIndex];

            $topCard->setLocation('deck');
            $bot->removeCard($card);
            $card->setLocation('pile');
            $card->setPlayer(null);
            $game->setTopCard($card);

            $skip = false;
            if ($card->getValue() === 'S') {
                if ($game->getDirection() === 'normal') {
                    $game->setDirection('reverse');
                } else {
                    $game->setDirection('normal');
                }
            } elseif ($card->getValue() === 'X') {
                $skip = true;
            } elseif ($card->getValue() === '+2') {
                $newPending = $game->getPendingDraw() + 2;
                $game->setPendingDraw($newPending);
            }

            $this->advanceTurn($game);
            if ($skip) {
                $this->advanceTurn($game);
            }

            if ($bot->getCards()->count() === 0) {
                $game->setStatus('finished');
            }
        } else {
            $deckCards = $em->getRepository(Card::class)->findBy(['location' => 'deck']);
            if (count($deckCards) === 0) {
                $this->recyclePile($game, $em);
            }
            $drawn = $em->getRepository(Card::class)->findRandomDeckCard();
            if ($drawn !== null) {
                $drawn->setLocation('player');
                $bot->addCard($drawn);
            }
            $this->advanceTurn($game);
        }

        $em->flush();

        return $this->redirectToRoute('app_play');
    }
}
