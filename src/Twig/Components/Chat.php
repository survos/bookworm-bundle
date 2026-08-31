<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Twig\Components;

use Survos\BookwormBundle\Chat\ChatSession;
use Symfony\UX\LiveComponent\Attribute\{AsLiveComponent,LiveAction,LiveProp};
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('Bookworm:Chat', template: '@SurvosBookworm/components/Chat.html.twig')]
final class Chat
{
    use DefaultActionTrait;
    #[LiveProp] public string $corpus;
    #[LiveProp(writable: true)] public string $message = '';
    public function __construct(private readonly ChatSession $chat) {}
    /** @return list<array{role: string, content: string}> */
    public function getMessages(): array { return $this->chat->messages($this->corpus); }
    #[LiveAction] public function submit(): void
    {
        if ('' === trim($this->message)) { return; }
        $this->chat->submit($this->corpus, $this->message); $this->message = '';
    }
    #[LiveAction] public function reset(): void { $this->chat->reset($this->corpus); }
}
