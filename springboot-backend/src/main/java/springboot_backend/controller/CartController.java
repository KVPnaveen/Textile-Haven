package springboot_backend.controller;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpSession;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import springboot_backend.dto.AddToCartRequest;
import springboot_backend.dto.ApiResponse;
import springboot_backend.dto.CartItemDTO;
import springboot_backend.dto.UpdateCartRequest;
import springboot_backend.service.CartService;

import java.math.BigDecimal;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/cart")
public class CartController {

    private final CartService cartService;

    public CartController(CartService cartService) {
        this.cartService = cartService;
    }

    private Long resolveUserId(Long headerUserId, Long paramUserId, HttpServletRequest request) {
        if (headerUserId != null) return headerUserId;
        if (paramUserId != null) return paramUserId;
        HttpSession session = request.getSession(false);
        if (session != null) {
            Object id = session.getAttribute("USER_ID");
            if (id instanceof Long) return (Long) id;
            if (id instanceof Integer) return ((Integer) id).longValue();
        }
        return null;
    }

    @GetMapping
    public ResponseEntity<ApiResponse<Map<String, Object>>> getCart(
            @RequestHeader(value = "X-User-Id", required = false) Long headerUserId,
            @RequestParam(value = "userId", required = false) Long paramUserId,
            HttpServletRequest request) {

        Long userId = resolveUserId(headerUserId, paramUserId, request);
        if (userId == null) {
            return ResponseEntity.badRequest().body(ApiResponse.error("Please login to view cart"));
        }

        List<CartItemDTO> items = cartService.getCartItems(userId);
        int count = cartService.getCartCount(userId);
        BigDecimal total = cartService.getCartTotal(userId);

        Map<String, Object> data = new HashMap<>();
        data.put("items", items);
        data.put("count", count);
        data.put("total", total);

        return ResponseEntity.ok(ApiResponse.ok("Cart items", data));
    }

    @PostMapping
    public ResponseEntity<ApiResponse<CartItemDTO>> addToCart(
            @RequestBody AddToCartRequest req,
            @RequestHeader(value = "X-User-Id", required = false) Long headerUserId,
            @RequestParam(value = "userId", required = false) Long paramUserId,
            HttpServletRequest request) {

        Long userId = resolveUserId(headerUserId, paramUserId, request);
        if (userId == null) {
            return ResponseEntity.badRequest().body(ApiResponse.error("Please login to add items to cart"));
        }

        if (req.getProductId() == null) {
            return ResponseEntity.badRequest().body(ApiResponse.error("Product ID is required"));
        }

        try {
            int qty = req.getQuantity() != null && req.getQuantity() > 0 ? req.getQuantity() : 1;
            CartItemDTO item = cartService.addToCart(userId, req.getProductId(), req.getSizeId(), qty);
            return ResponseEntity.ok(ApiResponse.ok("Item added to cart!", item));
        } catch (Exception e) {
            return ResponseEntity.badRequest().body(ApiResponse.error(e.getMessage()));
        }
    }

    @PutMapping("/{cartId}")
    public ResponseEntity<ApiResponse<Void>> updateQuantity(
            @PathVariable Long cartId,
            @RequestBody UpdateCartRequest req,
            @RequestHeader(value = "X-User-Id", required = false) Long headerUserId,
            @RequestParam(value = "userId", required = false) Long paramUserId,
            HttpServletRequest request) {

        Long userId = resolveUserId(headerUserId, paramUserId, request);
        if (userId == null) {
            return ResponseEntity.badRequest().body(ApiResponse.error("Please login to update cart"));
        }

        try {
            cartService.updateQuantity(userId, cartId, req.getQuantity());
            return ResponseEntity.ok(ApiResponse.ok("Cart updated successfully"));
        } catch (Exception e) {
            return ResponseEntity.badRequest().body(ApiResponse.error(e.getMessage()));
        }
    }

    @DeleteMapping("/{cartId}")
    public ResponseEntity<ApiResponse<Void>> removeFromCart(
            @PathVariable Long cartId,
            @RequestHeader(value = "X-User-Id", required = false) Long headerUserId,
            @RequestParam(value = "userId", required = false) Long paramUserId,
            HttpServletRequest request) {

        Long userId = resolveUserId(headerUserId, paramUserId, request);
        if (userId == null) {
            return ResponseEntity.badRequest().body(ApiResponse.error("Please login to remove item from cart"));
        }

        try {
            cartService.removeFromCart(userId, cartId);
            return ResponseEntity.ok(ApiResponse.ok("Item removed from cart"));
        } catch (Exception e) {
            return ResponseEntity.badRequest().body(ApiResponse.error(e.getMessage()));
        }
    }

    @DeleteMapping
    public ResponseEntity<ApiResponse<Void>> clearCart(
            @RequestHeader(value = "X-User-Id", required = false) Long headerUserId,
            @RequestParam(value = "userId", required = false) Long paramUserId,
            HttpServletRequest request) {

        Long userId = resolveUserId(headerUserId, paramUserId, request);
        if (userId == null) {
            return ResponseEntity.badRequest().body(ApiResponse.error("Please login to clear cart"));
        }

        try {
            cartService.clearCart(userId);
            return ResponseEntity.ok(ApiResponse.ok("Cart cleared"));
        } catch (Exception e) {
            return ResponseEntity.badRequest().body(ApiResponse.error(e.getMessage()));
        }
    }
}
