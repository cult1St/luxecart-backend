<?php 
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\ProductService;
use Core\Database;
use Core\Request;
use Core\Response;
use Helpers\Admin\ProductValidator;
use Helpers\ClientLang;
use Helpers\ErrorResponse;
use Throwable;

/**
 * Product Controller
 *
 * Manages admin product operations
 */
class ProductController extends BaseController 
{
    private ProductService $productService;
    public function __construct(Database $db, Request $request, Response $response)
    {
        $this->productService = new ProductService($db);
        return parent::__construct($db, $request, $response);
    }

    /**
     * get paginated products listing
     */
    public function index(){
        $this->requireAdmin();
        $page = $this->request->get("page",1);
        $per_page = $this->request->get('per_page', 10);

        try{
            $paginatedData = $this->productService->getAdminPaginatedProducts($page, $per_page);
        }catch(Throwable $th){
            return $this->response->error(ErrorResponse::formatResponse($th));
        }
        return $this->response->success($paginatedData, "Products fetched successfully");
    }

    /**
     * Post request to add new products
     */
    public function store(){
        $this->requireAdmin();
        $input = $this->request->all();
        try{
            $validator = ProductValidator::validate($input);
            if(!$validator['sanitizeData']){
                return $this->response->error(ClientLang::REQUIRED_FIELDS, 412, $validator['errors']);
            }
            $product = $this->productService->createProduct($validator['sanitizeData']);
            
            return $this->response->success($product, "Product Created Successfully", 201);
        }catch(Throwable $th){
            return $this->response->error(ErrorResponse::formatResponse($th));
        }
    }

    /**
     * Get a Product
     */
    public function show(int $id){
        $this->requireAdmin();
        try{
            $product = $this->productService->getProduct($id);
            return $this->response->success($product, "Product fetched successfully");
        }catch(Throwable $th){
            return $this->response->error(ErrorResponse::formatResponse($th));
        }
    }

    /**
     * Update a Product
     */
    public function update(int $id){
        $this->requireAdmin();
        try{
            $input = $this->request->all();
            $product = $this->productService->updateProduct($id, $input);
            return $this->response->success($product, "Product Updated Successfully");
        }catch(Throwable $th){
            return $this->response->error(ErrorResponse::formatResponse($th));
        }
    }

    /**
     * Delete a Product
     */
    public function destroy(int $id){
        $this->requireAdmin();
        try{
            $delete = $this->productService->deleteProduct($id);
        }catch(Throwable $th){
            return $this->response->error(ErrorResponse::formatResponse($th));
        }
    }

    /**
     * Get the next product id to be created
     */
    public function getNextProductId(){
        $this->requireAdmin();
        try{
            $nextId = $this->productService->getNextProductId();
            return $this->response->success(['next_product_id' => $nextId], "Next Product ID fetched successfully");
        }catch(Throwable $th){
            return $this->response->error(ErrorResponse::formatResponse($th));
        }
    }
}