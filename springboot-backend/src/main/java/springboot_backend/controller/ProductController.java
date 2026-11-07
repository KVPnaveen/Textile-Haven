package springboot_backend.controller;

import org.springframework.data.domain.Page;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;
import springboot_backend.dto.ApiResponse;
import springboot_backend.dto.ProductDTO;
import springboot_backend.service.ProductService;

import java.math.BigDecimal;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/products")
public class ProductController {

    private final ProductService productService;

    public ProductController(ProductService productService) {
        this.productService = productService;
    }

    @GetMapping
    public ResponseEntity<ApiResponse<Map<String, Object>>> getProducts(
            @RequestParam(value = "search", required = false) String search,
            @RequestParam(value = "page", defaultValue = "1") int page,
            @RequestParam(value = "size", defaultValue = "12") int size,
            @RequestParam(value = "all", defaultValue = "false") boolean all) {

        if (all) {
            List<ProductDTO> allProducts = productService.getAllProductsList();
            Map<String, Object> response = new HashMap<>();
            response.put("products", allProducts);
            response.put("total", allProducts.size());
            return ResponseEntity.ok(ApiResponse.ok("All products", response));
        }

        Page<ProductDTO> productPage = productService.getProducts(search, page, size);

        Map<String, Object> response = new HashMap<>();
        response.put("products", productPage.getContent());
        response.put("total", productPage.getTotalElements());
        response.put("pages", productPage.getTotalPages());
        response.put("currentPage", page);

        return ResponseEntity.ok(ApiResponse.ok("Products retrieved", response));
    }

    @GetMapping("/featured")
    public ResponseEntity<ApiResponse<List<ProductDTO>>> getFeaturedProducts(
            @RequestParam(value = "limit", defaultValue = "6") int limit) {
        List<ProductDTO> featured = productService.getFeaturedProducts(limit);
        return ResponseEntity.ok(ApiResponse.ok("Featured products", featured));
    }

    @GetMapping("/{id}")
    public ResponseEntity<ApiResponse<ProductDTO>> getProductById(@PathVariable Long id) {
        try {
            ProductDTO product = productService.getProductById(id);
            return ResponseEntity.ok(ApiResponse.ok("Product found", product));
        } catch (Exception e) {
            return ResponseEntity.badRequest().body(ApiResponse.error(e.getMessage()));
        }
    }

    @PostMapping(consumes = {MediaType.MULTIPART_FORM_DATA_VALUE})
    public ResponseEntity<ApiResponse<ProductDTO>> createProduct(
            @RequestParam("name") String name,
            @RequestParam("description") String description,
            @RequestParam("price") BigDecimal price,
            @RequestParam(value = "image1", required = false) MultipartFile image1,
            @RequestParam(value = "image2", required = false) MultipartFile image2,
            @RequestParam Map<String, String> allParams) {

        try {
            Map<Long, Integer> sizes = extractSizes(allParams);
            ProductDTO created = productService.createProduct(name, description, price, image1, image2, sizes);
            return ResponseEntity.ok(ApiResponse.ok("Product created successfully", created));
        } catch (Exception e) {
            return ResponseEntity.badRequest().body(ApiResponse.error("Failed to create product: " + e.getMessage()));
        }
    }

    @PutMapping(value = "/{id}", consumes = {MediaType.MULTIPART_FORM_DATA_VALUE})
    public ResponseEntity<ApiResponse<ProductDTO>> updateProduct(
            @PathVariable Long id,
            @RequestParam("name") String name,
            @RequestParam("description") String description,
            @RequestParam("price") BigDecimal price,
            @RequestParam(value = "image1", required = false) MultipartFile image1,
            @RequestParam(value = "image2", required = false) MultipartFile image2,
            @RequestParam Map<String, String> allParams) {

        try {
            Map<Long, Integer> sizes = extractSizes(allParams);
            ProductDTO updated = productService.updateProduct(id, name, description, price, image1, image2, sizes);
            return ResponseEntity.ok(ApiResponse.ok("Product updated successfully", updated));
        } catch (Exception e) {
            return ResponseEntity.badRequest().body(ApiResponse.error("Failed to update product: " + e.getMessage()));
        }
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<ApiResponse<Void>> deleteProduct(@PathVariable Long id) {
        try {
            productService.deleteProduct(id);
            return ResponseEntity.ok(ApiResponse.ok("Product deleted successfully"));
        } catch (Exception e) {
            return ResponseEntity.badRequest().body(ApiResponse.error("Failed to delete product: " + e.getMessage()));
        }
    }

    private Map<Long, Integer> extractSizes(Map<String, String> params) {
        Map<Long, Integer> sizes = new HashMap<>();
        for (Map.Entry<String, String> entry : params.entrySet()) {
            String key = entry.getKey();
            if (key.startsWith("size_")) {
                try {
                    Long sizeId = Long.parseLong(key.substring(5));
                    int quantity = Integer.parseInt(entry.getValue());
                    sizes.put(sizeId, quantity);
                } catch (NumberFormatException ignored) {
                }
            } else if (key.startsWith("size[")) {
                try {
                    Long sizeId = Long.parseLong(key.substring(5, key.length() - 1));
                    int quantity = Integer.parseInt(entry.getValue());
                    sizes.put(sizeId, quantity);
                } catch (NumberFormatException ignored) {
                }
            }
        }
        return sizes;
    }
}
