<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Utils\Contracts\Arrayable;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidationException;
use Psr\Container\ContainerInterface;

/**
 * Class AbstractController
 * @package App\Controller
 */
abstract class AbstractController
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @Inject
     * @var ResponseInterface
     */
    protected ResponseInterface $response;

    /**
     * @Inject
     * @var ValidatorFactoryInterface
     */
    protected ValidatorFactoryInterface $validationFactory;

    /**
     * 返回逻辑错误
     * @param $message
     * @param $errorCode
     * @return array
     */
    public function errorResponse($message, $errorCode)
    {
        return [
            'code' => $errorCode,
            'message' => $message
        ];
    }

    /**
     * 返回成功信息及内容
     * @param array|Arrayable $data
     * @param string $message
     * @return array
     */
    public function successResponse($data = [], $message = 'OK')
    {
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }
        return [
            'code' => 200,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        $page = (int)$this->request->input('page', 1);
        if ($page < 1) {
            return 1;
        }
        return $page;
    }

    /**
     * @param array $roles
     * @param array $messages
     * @return array $allowed_keys
     * @throws ValidationException
     */
    public function validation(array $roles, array $messages = []): array
    {
        $validator = $this->validationFactory->make($this->request->all(), $roles, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        return array_keys($roles);
    }
}
