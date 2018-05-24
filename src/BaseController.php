<?php

namespace rgen3\controller\json;

use yii\base\Action;
use yii\base\InvalidRouteException;
use yii\base\Model;
use yii\helpers\Json;
use yii\rest\Controller;
use yii\web\Application;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class BaseController extends Controller
{
    /** @var array */
    private $errorContext = [];

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * @param Action $action
     * @param array $params
     * @return array
     * @throws \yii\web\BadRequestHttpException
     */
    public function bindActionParams($action, $params)
    {
        $reflectionMethod = new \ReflectionMethod(static::class, $action->actionMethod);
        $reflectionParameters = $reflectionMethod->getParameters();
        if (!empty($reflectionParameters)) {
            $reflectionParameter = $reflectionParameters[0];
            $requestClass = $reflectionParameter->getType()->getName();
            if (class_exists($requestClass)) {
                /** @var Model $request */
                $request = new $requestClass();
                $data = Json::decode(\Yii::$app->getRequest()->getRawBody());
                $request->load($data, 'data');
                if (!$request->validate()) {
                    $this->errorContext = $request->getErrors();
                    throw new BadRequestHttpException('Bad request');
                }
                $params = [$reflectionParameter->getName() => $request];
            }
        }

        return parent::bindActionParams($action, $params);
    }

    /**
     * @param string $id
     * @param array $params
     * @return array|mixed
     */
    public function runAction($id, $params = [])
    {
        try {
            return $this->prepareAction($id, $params);
        } catch (HttpException $e) {
            \Yii::$app->response->setStatusCode($e->statusCode);
            \Yii::$app->response->statusText = $e->getMessage();
            $this->errorJson($this->errorContext);
        } catch (\Throwable $e) {
            \Yii::$app->response->setStatusCode(500);
            $this->errorContext[] = $e->getMessage();
            $this->errorJson($this->errorContext);
        }
    }

    /**
     * @param $id
     * @param $params
     * @return mixed
     * @throws NotFoundHttpException
     */
    private function prepareAction($id, $params)
    {
        try {
            return parent::runAction($id, $params);
        } catch (InvalidRouteException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
    }

    /**
     * @param array $data
     * @return array
     */
    protected function success(array $data)
    {
        return $this->pack($data, 'success');
    }

    /**
     * @param array $data
     * @return array
     */
    protected function error(array $data)
    {
        return $this->pack($data, 'error');
    }

    /**
     * @param array $data
     * @throws \yii\base\ExitException
     */
    protected function errorJson(array $data)
    {
        echo json_encode(
            $this->error($data),
            JSON_UNESCAPED_UNICODE
        );
        \Yii::$app->state = Application::STATE_END;
        \Yii::$app->end();
    }

    /**
     * @param $data
     * @param $status
     * @return array
     */
    protected function pack($data, $status) {
        return [
            'request-id' =>  \Yii::$app->request->getHeaders()->get('X-Request-Id'),
            'status-code' => \Yii::$app->response->statusCode,
            'status-text' => \Yii::$app->response->statusText,
            'status' => $status,
            'data' => $data
        ];
    }
}