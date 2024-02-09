<?php

namespace App\Controller;

use App\SDK\PrintoclockBAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

class OrderController extends AbstractController
{
    /**
     * @var PrintoclockBAPI
     */
    protected $businessApiClient;

    public function __construct(PrintoclockBAPI $businessApiClient)
    {
        $this->businessApiClient = $businessApiClient;
    }

    /**
     * @Route("/orders", methods={"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createAction(Request $request)
    {
        $data = $request->request->all();

        return new JsonResponse($this->businessApiClient->createOrder($data));
    }

    /**
     * @Route("/orders", methods={"GET"})
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function indexAction(Request $request)
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', PrintoclockBAPI::DEFAULT_PAGE_LIMIT);
        $response = $this->businessApiClient->getOrders($page, $limit);
        $orders = json_decode($response->getBody()->getContents(), true);
        $availableLinks = $this->businessApiClient->getLinksFromResponse($response);

        // Boucle à travers chaque élément de commande
        // Boucle à travers chaque élément de commande
        foreach ($orders as &$order) {
            foreach ($order['items'] as &$item) {
                // Obtenez le code du produit pour l'élément de la commande
                $code = $item['productCode'];

                // Obtenez les détails du produit en utilisant getProduct
                $product = $this->businessApiClient->getProduct($code);
                // var_dump($item['sourceDocuments'][0]['thumbnailDocuments'][0]['path']);

                // Vérifiez si le produit existe et s'il a des images
                if (isset($product['images'])) {
                    // Ajoutez les chemins d'accès des images du produit aux données de l'élément de commande
                    $item['images'] = $product['images'];
                } else {
                    // Si le produit n'a pas d'images, définissez la valeur sur null ou une valeur par défaut
                    $item['images'] = null; // ou No image par exemple
                }
            }
        }

        return $this->render('orders.html.twig', array(
            'orders' => $orders,
            'links' => $availableLinks,
        ));
    }
}
