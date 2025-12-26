<?php

declare(strict_types=1);

use Ratoufa\Messaging\Contracts\WhatsAppGatewayInterface;
use Ratoufa\Messaging\Data\BulkMessage;
use Ratoufa\Messaging\Data\PersonalizedMessage;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Enums\ResponseCode;
use Ratoufa\Messaging\Facades\WhatsApp;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Api\V2010\Account\MessageList;
use Twilio\Rest\Client;

beforeEach(function (): void {
    // Create a mock MessageInstance
    $this->mockMessage = Mockery::mock(MessageInstance::class);
    $this->mockMessage->sid = 'SM123456';
    $this->mockMessage->status = 'queued';
    $this->mockMessage->dateCreated = new DateTimeImmutable();
    $this->mockMessage->direction = 'outbound-api';

    // Create mock MessageList
    $this->mockMessageList = Mockery::mock(MessageList::class);
    $this->mockMessageList->shouldReceive('create')
        ->andReturn($this->mockMessage);

    // Create mock Client
    $this->mockClient = Mockery::mock(Client::class);
    $this->mockClient->messages = $this->mockMessageList;

    // Create a mock gateway that uses our mock client
    $mockGateway = new readonly class($this->mockMessage) implements WhatsAppGatewayInterface
    {
        public function __construct(
            private MessageInstance $mockMessage,
        ) {}

        public function send(SmsMessage $message): Response
        {
            return new Response(
                success: true,
                code: ResponseCode::SUCCESS,
                message: 'Message sent',
                resourceId: $this->mockMessage->sid,
            );
        }

        public function sendBulk(BulkMessage $message): Response
        {
            $results = [];
            foreach ($message->recipients as $recipient) {
                $results[] = [
                    'phone' => $recipient,
                    'success' => true,
                    'resourceId' => $this->mockMessage->sid,
                ];
            }

            return new Response(
                success: true,
                code: ResponseCode::SUCCESS,
                message: 'All messages sent',
                data: ['results' => $results],
            );
        }

        public function sendPersonalized(array $messages, ?string $senderId = null): Response
        {
            $results = [];
            foreach ($messages as $msg) {
                $results[] = [
                    'phone' => $msg->recipient,
                    'success' => true,
                    'resourceId' => $this->mockMessage->sid,
                ];
            }

            return new Response(
                success: true,
                code: ResponseCode::SUCCESS,
                message: 'All messages sent',
                data: ['results' => $results],
            );
        }

        public function getBalance(): Illuminate\Support\Collection
        {
            return collect([
                new Ratoufa\Messaging\Data\BalanceInfo('Twilio Account', 100),
            ]);
        }

        public function sendTemplate(string $recipient, string $contentSid, array $variables = []): Response
        {
            return new Response(
                success: true,
                code: ResponseCode::SUCCESS,
                message: 'Template sent',
                resourceId: $this->mockMessage->sid,
            );
        }

        public function sendMedia(string $recipient, string $mediaUrl, ?string $caption = null): Response
        {
            return new Response(
                success: true,
                code: ResponseCode::SUCCESS,
                message: 'Media sent',
                resourceId: $this->mockMessage->sid,
            );
        }
    };

    app()->instance(WhatsAppGatewayInterface::class, $mockGateway);
});

afterEach(function (): void {
    Mockery::close();
});

describe('send', function (): void {
    it('sends a whatsapp message', function (): void {
        $response = WhatsApp::to('22890123456')->send('Hello WhatsApp!');

        expect($response->success)->toBeTrue();
        expect($response->code)->toBe(ResponseCode::SUCCESS);
        expect($response->resourceId)->toBe('SM123456');
    });

    it('sends message using SmsMessage object', function (): void {
        $message = new SmsMessage(
            recipient: '22890123456',
            content: 'WhatsApp test',
        );

        $response = WhatsApp::send($message);

        expect($response->success)->toBeTrue();
    });
});

describe('sendBulk', function (): void {
    it('sends bulk whatsapp messages', function (): void {
        $message = new BulkMessage(
            recipients: ['22890123456', '22891234567'],
            content: 'Bulk WhatsApp',
        );

        $response = WhatsApp::sendBulk($message);

        expect($response->success)->toBeTrue();
        expect($response->data['results'])->toHaveCount(2);
    });
});

describe('sendPersonalized', function (): void {
    it('sends personalized whatsapp messages', function (): void {
        $messages = [
            new PersonalizedMessage('22890123456', 'Hello John'),
            new PersonalizedMessage('22891234567', 'Hello Jane'),
        ];

        $response = WhatsApp::sendPersonalized($messages);

        expect($response->success)->toBeTrue();
        expect($response->data['results'])->toHaveCount(2);
    });
});

describe('sendTemplate', function (): void {
    it('sends a template message', function (): void {
        $response = WhatsApp::sendTemplate(
            '22890123456',
            'HXb5a34a7e18eb123456789',
            ['1' => 'John', '2' => 'Order #123']
        );

        expect($response->success)->toBeTrue();
        expect($response->resourceId)->toBe('SM123456');
    });

    it('sends template without variables', function (): void {
        $response = WhatsApp::sendTemplate('22890123456', 'HXb5a34a7e18eb123456789');

        expect($response->success)->toBeTrue();
    });
});

describe('sendMedia', function (): void {
    it('sends media message', function (): void {
        $response = WhatsApp::sendMedia(
            '22890123456',
            'https://example.com/image.jpg',
            'Check this out!'
        );

        expect($response->success)->toBeTrue();
        expect($response->resourceId)->toBe('SM123456');
    });

    it('sends media without caption', function (): void {
        $response = WhatsApp::sendMedia(
            '22890123456',
            'https://example.com/document.pdf'
        );

        expect($response->success)->toBeTrue();
    });
});

describe('getBalance', function (): void {
    it('retrieves account balance', function (): void {
        $balances = WhatsApp::getBalance();

        expect($balances)->toHaveCount(1);
        expect($balances->first()->country)->toBe('Twilio Account');
    });
});

describe('error handling', function (): void {
    it('returns TEMPLATE_REQUIRED for error 63016 (24h window expired)', function (): void {
        // Create a mock gateway that simulates the 63016 error
        $mockGateway = new readonly class implements WhatsAppGatewayInterface
        {
            public function send(SmsMessage $message): Response
            {
                return new Response(
                    success: false,
                    code: ResponseCode::TEMPLATE_REQUIRED,
                    message: 'Failed to send freeform message because you are outside the allowed window.',
                    data: ['error_code' => 63016],
                );
            }

            public function sendBulk(BulkMessage $message): Response
            {
                return new Response(success: false, code: ResponseCode::UNKNOWN, message: 'Error');
            }

            public function sendPersonalized(array $messages, ?string $senderId = null): Response
            {
                return new Response(success: false, code: ResponseCode::UNKNOWN, message: 'Error');
            }

            public function getBalance(): Illuminate\Support\Collection
            {
                return collect([]);
            }

            public function sendTemplate(string $recipient, string $contentSid, array $variables = []): Response
            {
                return new Response(success: true, code: ResponseCode::SUCCESS, message: 'Template sent');
            }

            public function sendMedia(string $recipient, string $mediaUrl, ?string $caption = null): Response
            {
                return new Response(success: false, code: ResponseCode::UNKNOWN, message: 'Error');
            }
        };

        app()->instance(WhatsAppGatewayInterface::class, $mockGateway);

        $response = WhatsApp::to('22890123456')->send('Hello!');

        expect($response->success)->toBeFalse();
        expect($response->code)->toBe(ResponseCode::TEMPLATE_REQUIRED);
        expect($response->data['error_code'])->toBe(63016);
    });

    it('has TEMPLATE_REQUIRED code with proper description', function (): void {
        $description = ResponseCode::TEMPLATE_REQUIRED->description();

        expect($description)->toContain('24-hour');
        expect($description)->toContain('Template');
    });
});
