package springboot_backend.controller;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import springboot_backend.dto.ApiResponse;
import springboot_backend.dto.ProductSizeDTO;
import springboot_backend.entity.Size;
import springboot_backend.service.SizeService;

import java.util.List;

@RestController
@RequestMapping("/api/sizes")
public class SizeController {

    private final SizeService sizeService;

    public SizeController(SizeService sizeService) {
        this.sizeService = sizeService;
    }

    @GetMapping
    public ResponseEntity<ApiResponse<List<Size>>> getAllSizes() {
        List<Size> sizes = sizeService.getAllSizes();
        return ResponseEntity.ok(ApiResponse.ok("Sizes retrieved", sizes));
    }

    @GetMapping("/product/{productId}")
    public ResponseEntity<ApiResponse<List<ProductSizeDTO>>> getProductSizes(@PathVariable Long productId) {
        List<ProductSizeDTO> sizes = sizeService.getProductSizes(productId);
        return ResponseEntity.ok(ApiResponse.ok("Product sizes retrieved", sizes));
    }
}
