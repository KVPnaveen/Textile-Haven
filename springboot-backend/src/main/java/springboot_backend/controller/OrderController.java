package springboot_backend.controller;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpSession;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import springboot_backend.dto.ApiResponse;
import springboot_backend.dto.CheckoutRequest;
import springboot_backend.service.OrderService;

import java.util.Map;

@RestController
@RequestMapping("/api/checkout")
public class OrderController {

    private final OrderService orderService;

    public OrderController(OrderService orderService) {
        this.orderService = orderService;
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

    @PostMapping
    public ResponseEntity<ApiResponse<Map<String, Object>>> checkout(
            @RequestBody(required = false) CheckoutRequest checkoutRequest,
            @RequestHeader(value = "X-User-Id", required = false) Long headerUserId,
            @RequestParam(value = "userId", required = false) Long paramUserId,
            HttpServletRequest request) {

        Long userId = resolveUserId(headerUserId, paramUserId, request);
        if (userId == null) {
            return ResponseEntity.badRequest().body(ApiResponse.error("Please login to proceed to checkout"));
        }

        try {
            Map<String, Object> orderDetails = orderService.processCheckout(userId, checkoutRequest);
            return ResponseEntity.ok(ApiResponse.ok("Order placed successfully!", orderDetails));
        } catch (IllegalStateException e) {
            return ResponseEntity.badRequest().body(ApiResponse.error(e.getMessage()));
        } catch (Exception e) {
            return ResponseEntity.internalServerError().body(ApiResponse.error("Failed to process order: " + e.getMessage()));
        }
    }
}
