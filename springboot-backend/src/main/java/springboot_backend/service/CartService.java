package springboot_backend.service;

import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import springboot_backend.dto.CartItemDTO;
import springboot_backend.entity.CartItem;
import springboot_backend.entity.Product;
import springboot_backend.entity.ProductSize;
import springboot_backend.entity.Size;
import springboot_backend.entity.User;
import springboot_backend.repository.*;

import java.math.BigDecimal;
import java.util.ArrayList;
import java.util.List;
import java.util.NoSuchElementException;
import java.util.Optional;

@Service
public class CartService {

    private final CartRepository cartRepository;
    private final ProductRepository productRepository;
    private final SizeRepository sizeRepository;
    private final UserRepository userRepository;
    private final ProductSizeRepository productSizeRepository;

    public CartService(CartRepository cartRepository,
                       ProductRepository productRepository,
                       SizeRepository sizeRepository,
                       UserRepository userRepository,
                       ProductSizeRepository productSizeRepository) {
        this.cartRepository = cartRepository;
        this.productRepository = productRepository;
        this.sizeRepository = sizeRepository;
        this.userRepository = userRepository;
        this.productSizeRepository = productSizeRepository;
    }

    public List<CartItemDTO> getCartItems(Long userId) {
        List<CartItem> items = cartRepository.findByUserIdOrderByIdDesc(userId);
        List<CartItemDTO> dtoList = new ArrayList<>();

        for (CartItem item : items) {
            CartItemDTO dto = new CartItemDTO();
            dto.setId(item.getId());

            Product p = item.getProduct();
            dto.setProductId(p.getId());
            dto.setProductName(p.getName());
            dto.setProductDescription(p.getDescription());
            dto.setPrice(p.getPrice());
            dto.setImage1(p.getImage1());
            dto.setImage2(p.getImage2());

            String mainImg = p.getImage1() != null ? p.getImage1() : p.getImage();
            if (mainImg != null && !mainImg.startsWith("/") && !mainImg.startsWith("http")) {
                mainImg = "/" + mainImg;
            }
            dto.setMainImage(mainImg);

            if (item.getSize() != null) {
                dto.setSizeId(item.getSize().getId());
                dto.setSizeName(item.getSize().getName());
                dto.setSizeDisplayName(item.getSize().getDisplayName());

                Optional<ProductSize> ps = productSizeRepository.findByProductIdAndSizeId(p.getId(), item.getSize().getId());
                dto.setMaxStock(ps.map(ProductSize::getStockQuantity).orElse(0));
            } else {
                dto.setMaxStock(999);
            }

            dto.setQuantity(item.getQuantity());
            dto.setSubtotal(p.getPrice().multiply(BigDecimal.valueOf(item.getQuantity())));

            dtoList.add(dto);
        }

        return dtoList;
    }

    public int getCartCount(Long userId) {
        Integer count = cartRepository.countTotalItemsByUserId(userId);
        return count != null ? count : 0;
    }

    public BigDecimal getCartTotal(Long userId) {
        List<CartItem> items = cartRepository.findByUserIdOrderByIdDesc(userId);
        BigDecimal total = BigDecimal.ZERO;
        for (CartItem item : items) {
            BigDecimal sub = item.getProduct().getPrice().multiply(BigDecimal.valueOf(item.getQuantity()));
            total = total.add(sub);
        }
        return total;
    }

    @Transactional
    public CartItemDTO addToCart(Long userId, Long productId, Long sizeId, int quantity) {
        if (quantity <= 0) quantity = 1;

        User user = userRepository.findById(userId)
                .orElseThrow(() -> new NoSuchElementException("User not found"));
        Product product = productRepository.findById(productId)
                .orElseThrow(() -> new NoSuchElementException("Product not found"));

        Size size = null;
        if (sizeId != null && sizeId > 0) {
            size = sizeRepository.findById(sizeId).orElse(null);
        }

        Optional<CartItem> existingItem;
        if (size != null) {
            existingItem = cartRepository.findByUserIdAndProductIdAndSizeId(userId, productId, size.getId());
        } else {
            existingItem = cartRepository.findByUserIdAndProductIdAndSizeIsNull(userId, productId);
        }

        CartItem cartItem;
        if (existingItem.isPresent()) {
            cartItem = existingItem.get();
            cartItem.setQuantity(cartItem.getQuantity() + quantity);
        } else {
            cartItem = new CartItem(user, product, size, quantity);
        }

        cartItem = cartRepository.save(cartItem);

        CartItemDTO dto = new CartItemDTO();
        dto.setId(cartItem.getId());
        dto.setProductId(product.getId());
        dto.setProductName(product.getName());
        dto.setQuantity(cartItem.getQuantity());
        return dto;
    }

    @Transactional
    public void updateQuantity(Long userId, Long cartId, int quantity) {
        CartItem cartItem = cartRepository.findById(cartId)
                .orElseThrow(() -> new NoSuchElementException("Cart item not found"));

        if (!cartItem.getUser().getId().equals(userId)) {
            throw new IllegalArgumentException("Unauthorized to modify this cart item");
        }

        if (quantity <= 0) {
            cartRepository.delete(cartItem);
        } else {
            cartItem.setQuantity(quantity);
            cartRepository.save(cartItem);
        }
    }

    @Transactional
    public void removeFromCart(Long userId, Long cartId) {
        CartItem cartItem = cartRepository.findById(cartId)
                .orElseThrow(() -> new NoSuchElementException("Cart item not found"));

        if (!cartItem.getUser().getId().equals(userId)) {
            throw new IllegalArgumentException("Unauthorized to modify this cart item");
        }

        cartRepository.delete(cartItem);
    }

    @Transactional
    public void clearCart(Long userId) {
        cartRepository.deleteByUserId(userId);
    }
}

