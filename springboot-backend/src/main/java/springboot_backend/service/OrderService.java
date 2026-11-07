package springboot_backend.service;

import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import springboot_backend.dto.CheckoutRequest;
import springboot_backend.entity.CartItem;
import springboot_backend.entity.ProductSize;
import springboot_backend.repository.CartRepository;
import springboot_backend.repository.ProductSizeRepository;

import java.math.BigDecimal;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;

@Service
public class OrderService {

    private final CartRepository cartRepository;
    private final ProductSizeRepository productSizeRepository;

    public OrderService(CartRepository cartRepository, ProductSizeRepository productSizeRepository) {
        this.cartRepository = cartRepository;
        this.productSizeRepository = productSizeRepository;
    }

    @Transactional
    public Map<String, Object> processCheckout(Long userId, CheckoutRequest checkoutRequest) {
        List<CartItem> cartItems = cartRepository.findByUserIdOrderByIdDesc(userId);
        if (cartItems.isEmpty()) {
            throw new IllegalStateException("Your cart is empty");
        }

        BigDecimal totalAmount = BigDecimal.ZERO;

        // Check stock availability
        for (CartItem item : cartItems) {
            BigDecimal subtotal = item.getProduct().getPrice().multiply(BigDecimal.valueOf(item.getQuantity()));
            totalAmount = totalAmount.add(subtotal);

            if (item.getSize() != null) {
                Optional<ProductSize> psOpt = productSizeRepository.findByProductIdAndSizeId(
                        item.getProduct().getId(), item.getSize().getId());
                if (psOpt.isEmpty() || psOpt.get().getStockQuantity() < item.getQuantity()) {
                    throw new IllegalStateException("Item " + item.getProduct().getName() +
                            " (" + item.getSize().getName() + ") has insufficient stock.");
                }
            }
        }

        // Deduct stock
        for (CartItem item : cartItems) {
            if (item.getSize() != null) {
                ProductSize ps = productSizeRepository.findByProductIdAndSizeId(
                        item.getProduct().getId(), item.getSize().getId()).orElse(null);
                if (ps != null) {
                    ps.setStockQuantity(Math.max(0, ps.getStockQuantity() - item.getQuantity()));
                    productSizeRepository.save(ps);
                }
            } else {
                List<ProductSize> availableSizes = productSizeRepository.findByProductId(item.getProduct().getId());
                int remaining = item.getQuantity();
                for (ProductSize ps : availableSizes) {
                    if (remaining <= 0) break;
                    if (ps.getStockQuantity() > 0) {
                        int toUse = Math.min(ps.getStockQuantity(), remaining);
                        ps.setStockQuantity(ps.getStockQuantity() - toUse);
                        productSizeRepository.save(ps);
                        remaining -= toUse;
                    }
                }
            }
        }

        // Clear user's cart
        cartRepository.deleteByUserId(userId);

        String orderId = "ORD-" + LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyyMMdd")) +
                "-" + UUID.randomUUID().toString().substring(0, 6).toUpperCase();

        Map<String, Object> result = new HashMap<>();
        result.put("orderId", orderId);
        result.put("itemCount", cartItems.size());
        result.put("totalAmount", totalAmount);
        result.put("customer", checkoutRequest != null ? checkoutRequest.getFullName() : "");
        result.put("date", LocalDateTime.now().toString());

        return result;
    }
}

